# app.py
import os, math, re, traceback, logging, base64
from typing import List, Optional, Dict, Any

from fastapi import FastAPI, HTTPException, Header, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel, conint, constr
from sqlalchemy import create_engine, text, bindparam
from sqlalchemy.exc import SQLAlchemyError, OperationalError, ProgrammingError
from fastapi.middleware.cors import CORSMiddleware
import certifi
import google.generativeai as genai
from dotenv import load_dotenv
load_dotenv()

# =======================
# Konfigurasi & Logging
# =======================
DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_NAME = os.getenv("DB_NAME", "")
DB_USER = os.getenv("DB_USER", "root")
DB_PASS = os.getenv("DB_PASS", "")

PRICE_MARKUP = float(os.getenv("PRICE_MARKUP", "1.0"))
INTERNAL_TOKEN = os.getenv("INTERNAL_TOKEN", "super-secret-token")

GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "")
GEMINI_MODEL  = os.getenv("GEMINI_MODEL", "gemini-2.0-flash")
DEBUG = os.getenv("DEBUG", "0") in ("1", "true", "True")

# Hindari error SSL di Windows (cURL 60) dengan CA bundle certifi
os.environ.setdefault("SSL_CERT_FILE", certifi.where())
os.environ.setdefault("CURL_CA_BUNDLE", certifi.where())

LOG_LEVEL = logging.DEBUG if DEBUG else logging.INFO
logging.basicConfig(level=LOG_LEVEL, format="%(asctime)s [%(levelname)s] %(name)s: %(message)s")
logger = logging.getLogger("catalog-service")

DATABASE_URL = f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME}?charset=utf8mb4"

engine = create_engine(
    DATABASE_URL,
    pool_size=5,
    max_overflow=5,
    pool_pre_ping=True,
    future=True,
    echo=DEBUG,
)

if not GEMINI_API_KEY:
    logger.warning("GEMINI_API_KEY kosong. Endpoint /chat akan menolak request.")
else:
    genai.configure(api_key=GEMINI_API_KEY)

app = FastAPI(title="Catalog+Chat Service", version="3.0.0")
origins = [
    "http://localhost:8000",
    "http://127.0.0.1:8000",
]
app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"], # Izinkan semua method (GET, POST, dll)
    allow_headers=["*"], # Izinkan semua header, termasuk X-Internal-Token & X-CSRF-TOKEN
)
# =======================
# Pydantic Models
# =======================
class CatalogRequest(BaseModel):
    branch_id: conint(gt=0)
    q: Optional[constr(strip_whitespace=True, min_length=1)] = None
    cat_id: Optional[conint(gt=0)] = None

class ProductOut(BaseModel):
    name: str
    category_name: Optional[str] = None
    stock: int
    price: float

class ChatRequest(BaseModel):
    # Backend TIDAK simpan/olah history. Frontend kirim prompt penuh yang sudah berisi konteksnya.
    message: constr(strip_whitespace=True, min_length=1, max_length=100000)
    image: Optional[str] = None  # data URL: "data:image/png;base64,AAA..."
    # Agar kompatibel dengan caller lama, boleh kirim branch_id tapi backend akan mengabaikan.
    branch_id: Optional[int] = None

    class Config:
        extra = "ignore"  # abaikan field lain (mis. history) jika ikut terkirim

class ChatReply(BaseModel):
    reply: str

# =======================
# Auth sederhana via header
# =======================
def require_internal_token(x_internal_token: str = Header(default="")):
    if x_internal_token != INTERNAL_TOKEN:
        logger.warning("Unauthorized: X-Internal-Token tidak cocok")
        raise HTTPException(status_code=401, detail="Unauthorized")
    return True

# =======================
# Helper HPP → price (samakan regex PHP)
# PHP: /hpp\s*[:=]\s*([\d\.]+)/i
# =======================
HPP_RE = re.compile(r'hpp\s*[:=]\s*([\d\.]+)', re.IGNORECASE)

def price_from_notes(notes: Optional[str]) -> float:
    hpp = 0.0
    if notes:
        m = HPP_RE.search(str(notes))
        if m:
            try:
                hpp = float(m.group(1))
            except ValueError:
                hpp = 0.0
    price = hpp * PRICE_MARKUP
    return round(price if price else 0.0, 2)

# =======================
# Error helper
# =======================
def error_response(status: int, msg: str, exc: Exception | None = None) -> JSONResponse:
    payload: Dict[str, Any] = {"error": msg}
    if DEBUG and exc:
        payload["exception"] = str(exc)
        payload["trace"] = traceback.format_exc()
    return JSONResponse(status_code=status, content=payload)

# =======================
# Health
# =======================
@app.get("/health")
def health():
    try:
        with engine.connect() as conn:
            conn.exec_driver_sql("SELECT 1")
        return {"status": "ok"}
    except Exception as e:
        logger.exception("Health check DB gagal: %s", e)
        return JSONResponse(status_code=500, content={"status": "db_error", "detail": str(e)})

# =======================
# Query catalog (identik hasilnya dengan PHP)
# =======================
def query_catalog(conn, branch_id: int, q: Optional[str], cat_id: Optional[int]) -> List[Dict[str, Any]]:
    # Validasi cabang
    br = conn.execute(text("SELECT id FROM branches WHERE id = :bid"), {"bid": branch_id}).first()
    if not br:
        raise HTTPException(status_code=404, detail="Cabang tidak ditemukan.")

    # Ambil location ids
    loc_rows = conn.execute(text("SELECT id FROM stock_locations WHERE branch_id = :bid"), {"bid": branch_id}).all()
    loc_ids = [row[0] for row in loc_rows] or [-1]

    # Subquery stok ala PHP
    base_sql = """
        SELECT
            p.id,
            p.name,
            c.name AS category_name,
            p.notes,
            (
                SELECT IFNULL(SUM(sq.qty), 0)
                FROM stock_quants sq
                WHERE sq.product_id = p.id
                  AND sq.location_id IN :loc_ids
            ) AS stock
        FROM products p
        LEFT JOIN product_categories c ON p.category_id = c.id
        WHERE 1=1
    """

    filters_sql = ""
    params: Dict[str, Any] = {"loc_ids": loc_ids}

    if q:
        filters_sql += " AND (p.sku LIKE :like OR p.name LIKE :like)"
        params["like"] = f"%{q}%"
    if cat_id:
        filters_sql += " AND p.category_id = :cat_id"
        params["cat_id"] = int(cat_id)

    full_sql = base_sql + filters_sql + " ORDER BY p.name ASC"
    stmt = text(full_sql).bindparams(bindparam("loc_ids", expanding=True))

    if DEBUG:
        logger.debug("SQL Catalog: %s", full_sql)
        logger.debug("Params: %s", {k: (v if k != "loc_ids" else f"[{len(v)} ids]") for k, v in params.items()})

    rows = conn.execute(stmt, params).all()

    out: List[Dict[str, Any]] = []
    for r in rows:
        m = r._mapping
        out.append({
            "name": m.get("name", ""),
            "category_name": m.get("category_name"),
            "stock": int(math.floor(float(m.get("stock", 0) or 0))),
            "price": price_from_notes(m.get("notes")),
        })
    return out

# =======================
# /catalog – JSON harus sama persis dengan PHP
# =======================
@app.post("/catalog", response_model=List[ProductOut])
def get_catalog(req: CatalogRequest, _: bool = Depends(require_internal_token)):
    try:
        logger.info("POST /catalog body=%s", req.dict())
        with engine.begin() as conn:
            data = query_catalog(conn, req.branch_id, req.q, req.cat_id)
        return data
    except HTTPException:
        raise
    except (OperationalError, ProgrammingError) as db_e:
        logger.exception("Kesalahan DB (operational/programming): %s", db_e)
        return error_response(500, "DB error (operational/programming).", db_e)
    except SQLAlchemyError as db_e:
        logger.exception("Kesalahan DB umum: %s", db_e)
        return error_response(500, "DB error.", db_e)
    except Exception as e:
        logger.exception("Unexpected error: %s", e)
        return error_response(500, "Unexpected error.", e)

# =======================
# /chat – TANPA HISTORY di backend
# =======================
@app.post("/chat", response_model=ChatReply)
def chat(req: ChatRequest, _: bool = Depends(require_internal_token)):
    """
    Frontend WAJIB menyusun prompt yang sudah memuat konteks (katalog, instruksi, dsb).
    Backend hanya meneruskan "message" & (opsional) "image" ke Gemini dalam satu kali turn.
    """
    if not GEMINI_API_KEY:
        raise HTTPException(status_code=500, detail="GEMINI_API_KEY belum diset.")

    try:
        # 1) Siapkan parts user
        user_parts: List[Any] = [req.message]

        # 2) Jika ada gambar (data URL base64), parse & lampirkan
        if req.image:
            if "," not in req.image:
                raise HTTPException(status_code=400, detail="Format gambar tidak valid (data URL).")
            header, b64 = req.image.split(",", 1)
            if not header.startswith("data:image/"):
                raise HTTPException(status_code=415, detail="Hanya tipe gambar yang diizinkan.")
            mime = header.replace("data:", "").replace(";base64", "")
            if mime not in ("image/png", "image/jpeg", "image/webp"):
                raise HTTPException(status_code=415, detail="Format gambar tidak didukung.")
            if len(b64) > 6 * 1024 * 1024:
                raise HTTPException(status_code=413, detail="Gambar terlalu besar. Maks 5MB (base64).")
            try:
                blob = base64.b64decode(b64, validate=True)
            except Exception:
                raise HTTPException(status_code=400, detail="Base64 gambar korup.")
            user_parts.append({"mime_type": mime, "data": blob})

        # 3) Panggil Gemini (single-turn, tanpa history)
        model = genai.GenerativeModel(GEMINI_MODEL)
        response = model.generate_content(user_parts)

        reply_text = getattr(response, "text", None) or ""
        return {"reply": reply_text}

    except HTTPException:
        raise
    except Exception as e:
        logger.exception("Error di /chat: %s", e)
        return error_response(502, "Gagal berkomunikasi dengan Gemini API.", e)

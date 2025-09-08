import requests
import os

# Daftar proxy yang bisa dicoba
PROXIES_LIST = [
    {"http": "http://8.213.131.36:8080", "https": "http://8.213.131.36:8080"},      # Korea - TESTED ✅
    {"http": "http://157.151.157.214:8080", "https": "http://157.151.157.214:8080"}, # US - TESTED ✅
    {"http": "http://3.248.8.208:3128", "https": "http://3.248.8.208:3128"},        # Ireland - TESTED ✅
    {"http": "http://52.188.28.218:3128", "https": "http://52.188.28.218:3128"},    # US
    {"http": "http://179.61.251.217:8080", "https": "http://179.61.251.217:8080"},  # Germany
]

# Masukkan API key Gemini yang sebenarnya
GEMINI_API_KEY = "AIzaSyCbKDKi34BVEzSvCx8cNDZBVhQW3I9DARU"  # Ganti dengan API key Anda

def test_gemini_with_proxy(proxy, api_key):
    """Test Gemini API dengan proxy tertentu"""
    
    url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key={api_key}"
    
    headers = {
        "Content-Type": "application/json"
    }
    
    data = {
        "contents": [
            {
                "parts": [
                    {"text": "BAGAIMANA KABAR ANDA?"}
                ]
            }
        ]
    }
    
    try:
        print(f"Testing proxy: {proxy['http']}")
        response = requests.post(url, headers=headers, json=data, proxies=proxy, timeout=15)
        
        if response.status_code == 200:
            result = response.json()
            reply = result.get('candidates', [{}])[0].get('content', {}).get('parts', [{}])[0].get('text', '')
            print(f"✅ SUCCESS - Proxy working! Reply: {reply[:100]}...")
            return True
        else:
            print(f"❌ FAILED - Status: {response.status_code}, Response: {response.text[:200]}")
            return False
            
    except Exception as e:
        print(f"❌ ERROR - {str(e)}")
        return False

def main():
    """Test semua proxy"""
    
    if not GEMINI_API_KEY or GEMINI_API_KEY == "YOUR_API_KEY_HERE":
        print("❌ ERROR: Masukkan API key Gemini yang valid!")
        return
    
    print("🔍 Testing Gemini API dengan berbagai proxy...\n")
    
    working_proxies = []
    
    for i, proxy in enumerate(PROXIES_LIST, 1):
        print(f"[{i}/{len(PROXIES_LIST)}] ", end="")
        if test_gemini_with_proxy(proxy, GEMINI_API_KEY):
            working_proxies.append(proxy)
        print("-" * 50)
    
    print(f"\n🎯 HASIL: {len(working_proxies)} dari {len(PROXIES_LIST)} proxy berhasil")
    
    if working_proxies:
        print("\n✅ Proxy yang berfungsi:")
        for proxy in working_proxies:
            print(f"  - {proxy['http']}")
        
        # Return proxy terbaik untuk digunakan
        return working_proxies[0]
    else:
        print("\n❌ Tidak ada proxy yang berfungsi")
        return None

if __name__ == "__main__":
    best_proxy = main()
    
    if best_proxy:
        print(f"\n🚀 Gunakan proxy ini untuk FastAPI:")
        print(f"PROXY_HTTP = '{best_proxy['http']}'")
        print(f"PROXY_HTTPS = '{best_proxy['https']}'")


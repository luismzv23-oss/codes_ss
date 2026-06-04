import urllib.request
import json
import urllib.parse

api_key = "f0c0a08561154df8d34044b07ae65838a5a77a5d09272a36b7ba0768af5423ce"
query = urllib.parse.quote("Amistosos Internacionales Hoy")
url = f"https://serpapi.com/search.json?engine=google&q={query}&api_key={api_key}&hl=es&gl=ar"

try:
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    response = urllib.request.urlopen(req)
    data = json.loads(response.read().decode('utf-8'))
    with open('c:/Users/luism/.gemini/antigravity/scratch/codex_ss/serpapi_today_amistosos.json', 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
    print("Success: File written to serpapi_today_amistosos.json")
except Exception as e:
    print(f"Error: {e}")

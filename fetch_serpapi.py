import urllib.request
import json
import urllib.parse

api_key = "f0c0a08561154df8d34044b07ae65838a5a77a5d09272a36b7ba0768af5423ce"
query = urllib.parse.quote("champions league schedule")
url = f"https://serpapi.com/search.json?engine=google&q={query}&api_key={api_key}"

req = urllib.request.Request(url)
response = urllib.request.urlopen(req)
data = json.loads(response.read().decode('utf-8'))

with open('c:/Users/luism/.gemini/antigravity/scratch/codex_ss/serpapi_test.json', 'w') as f:
    json.dump(data, f, indent=2)

import urllib.request
import json
url = "https://api.the-odds-api.com/v4/sports/?apiKey=357002f026ea63c327e2af81e6d95dc4&all=true"
response = urllib.request.urlopen(url)
data = json.loads(response.read().decode('utf-8'))
with open('c:/Users/luism/.gemini/antigravity/scratch/codex_ss/sports.json', 'w') as f:
    json.dump(data, f)

import urllib.request
import json
url = "https://api.football-data.org/v4/competitions"
req = urllib.request.Request(url, headers={'X-Auth-Token': '99a866451b0746d3a903f9564cab1b9b'})
response = urllib.request.urlopen(req)
data = json.loads(response.read().decode('utf-8'))
with open('c:/Users/luism/.gemini/antigravity/scratch/codex_ss/football_data.json', 'w') as f:
    json.dump(data, f)

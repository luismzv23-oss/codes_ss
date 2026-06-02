import sys

with open('app/Views/dashboard/partials/events.php', 'r', encoding='utf-8') as f:
    content = f.read()

script_start = content.find('<script>')
if script_start == -1:
    print("No script found")
    sys.exit()

script_content = content[script_start:]
new_events_content = content[:script_start]

with open('app/Views/dashboard/partials/events.php', 'w', encoding='utf-8') as f:
    f.write(new_events_content)

with open('app/Views/layouts/main.php', 'r', encoding='utf-8') as f:
    main_content = f.read()

main_content = main_content.replace('</body>', script_content + '\n</body>')

with open('app/Views/layouts/main.php', 'w', encoding='utf-8') as f:
    f.write(main_content)

print("Done")

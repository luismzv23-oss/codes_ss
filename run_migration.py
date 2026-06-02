import sqlite3
import sys

db_path = 'writable/database.sqlite'
try:
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    cursor.execute("ALTER TABLE leagues ADD COLUMN sort_order INTEGER DEFAULT 0")
    conn.commit()
    conn.close()
    print("Column added successfully")
except Exception as e:
    print(f"Error: {e}")

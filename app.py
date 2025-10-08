from flask import Flask, render_template, request, jsonify, redirect, url_for, send_from_directory
import os
import json
from datetime import datetime
import sqlite3
from werkzeug.utils import secure_filename
import base64

app = Flask(__name__)

# Configuration
DATA_DIR = 'data'
FILES_DB = os.path.join(DATA_DIR, 'files.db')
LINKS_DB = os.path.join(DATA_DIR, 'links.db')
ACTIVITY_DB = os.path.join(DATA_DIR, 'activity.db')
UPLOAD_FOLDER = 'uploads'
ALLOWED_EXTENSIONS = {'txt', 'pdf', 'png', 'jpg', 'jpeg', 'gif', 'zip', 'rar'}
MAX_CONTENT_LENGTH = 16 * 1024 * 1024  # 16MB max file size

app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = MAX_CONTENT_LENGTH

def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def get_db_connection(db_path):
    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    """don't exist"""
    os.makedirs(DATA_DIR, exist_ok=True)
    os.makedirs(UPLOAD_FOLDER, exist_ok=True)
    
    # Files database
    conn = get_db_connection(FILES_DB)
    conn.execute('''CREATE TABLE IF NOT EXISTS files
                    (id INTEGER PRIMARY KEY AUTOINCREMENT,
                     filename TEXT NOT NULL,
                     content BLOB,
                     content_type TEXT,
                     size INTEGER,
                     uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                     original_name TEXT)''')
    conn.commit()
    conn.close()
    
    # Links database
    conn = get_db_connection(LINKS_DB)
    conn.execute('''CREATE TABLE IF NOT EXISTS links
                    (id INTEGER PRIMARY KEY AUTOINCREMENT,
                     short_code TEXT UNIQUE NOT NULL,
                     long_url TEXT NOT NULL,
                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                     last_access TIMESTAMP,
                     clicks INTEGER DEFAULT 0,
                     title TEXT)''')
    conn.commit()
    conn.close()
    
    # Activity database
    conn = get_db_connection(ACTIVITY_DB)
    conn.execute('''CREATE TABLE IF NOT EXISTS activity
                    (id INTEGER PRIMARY KEY AUTOINCREMENT,
                     action TEXT NOT NULL,
                     entity_type TEXT,
                     entity_id INTEGER,
                     details TEXT,
                     timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP)''')
    conn.commit()
    conn.close()

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/index.html')
def index_html():
    return render_template('index.html')

@app.route('/api/stats')
def api_stats():
    # Get files count
    conn = get_db_connection(FILES_DB)
    files_count = conn.execute('SELECT COUNT(*) FROM files').fetchone()[0]
    conn.close()
    
    # Get links count
    conn = get_db_connection(LINKS_DB)
    links_count = conn.execute('SELECT COUNT(*) FROM links').fetchone()[0]
    conn.close()
    
    stats = {
        'total_files': files_count,
        'total_links': links_count,
        'system_status': 'operational',
        'timestamp': datetime.now().isoformat()
    }
    return jsonify(stats)

@app.route('/api/files')
def api_files():
    conn = get_db_connection(FILES_DB)
    files = conn.execute('SELECT id, original_name as filename, size, uploaded_at FROM files ORDER BY uploaded_at DESC').fetchall()
    conn.close()
    
    files_list = []
    for file in files:
        files_list.append({
            'id': file['id'],
            'name': file['filename'],
            'size': file['size'],
            'date': file['uploaded_at']
        })
    
    return jsonify({'files': files_list})

@app.route('/api/upload', methods=['POST'])
def api_upload():
    if 'file' not in request.files:
        return jsonify({'success': False, 'message': 'No file provided'}), 400
    
    file = request.files['file']
    if file.filename == '':
        return jsonify({'success': False, 'message': 'No file selected'}), 400
    
    if file and allowed_file(file.filename):
        filename = secure_filename(file.filename)
        
        # Read file content
        content = file.read()
        content_type = file.content_type or 'application/octet-stream'
        size = len(content)
        
        # Save to database
        conn = get_db_connection(FILES_DB)
        cursor = conn.cursor()
        cursor.execute('INSERT INTO files (filename, content, content_type, size, original_name) VALUES (?, ?, ?, ?, ?)',
                       (f"{int(datetime.now().timestamp())}_{filename}", content, content_type, size, filename))
        file_id = cursor.lastrowid
        conn.commit()
        conn.close()
        
        # Log activity
        activity_conn = get_db_connection(ACTIVITY_DB)
        activity_conn.execute('INSERT INTO activity (action, entity_type, entity_id, details) VALUES (?, ?, ?, ?)',
                             ('file_upload', 'file', file_id, f'Uploaded file: {filename}'))
        activity_conn.commit()
        activity_conn.close()
        
        return jsonify({
            'success': True,
            'message': 'File uploaded successfully',
            'file': {
                'name': filename,
                'id': file_id,
                'storage_type': 'sqlite_data_branch'
            }
        })
    
    return jsonify({'success': False, 'message': 'Invalid file type'}), 400

def generate_short_code(length=6):
    import random
    import string
    chars = string.ascii_letters + string.digits
    return ''.join(random.choice(chars) for _ in range(length))

@app.route('/api/shorten', methods=['POST', 'GET'])
def api_shorten():
    if request.method == 'POST':
        data = request.get_json()
        long_url = data.get('url') if data else None
        title = data.get('title', '') if data else ''
    else:  # GET request for form data
        long_url = request.form.get('url')
        title = request.form.get('title', '')
    
    if not long_url:
        return jsonify({'success': False, 'message': 'Invalid URL provided'}), 400
    
    # Validate URL format
    import re
    url_pattern = re.compile(
        r'^https?://'  # http:// or https://
        r'(?:(?:[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?\.)+[A-Z]{2,6}\.?|'  # domain...
        r'localhost|'  # localhost...
        r'\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})'  # ...or ip
        r'(?::\d+)?'  # optional port
        r'(?:/?|[/?]\S+)$', re.IGNORECASE)
    
    if not url_pattern.match(long_url):
        return jsonify({'success': False, 'message': 'Invalid URL format'}), 400
    
    # Generate short code
    conn = get_db_connection(LINKS_DB)
    
    # Keep trying until we find a unique short code
    short_code = generate_short_code()
    while conn.execute('SELECT 1 FROM links WHERE short_code = ?', (short_code,)).fetchone():
        short_code = generate_short_code()
    
    # Insert the new link
    conn.execute('INSERT INTO links (short_code, long_url, title) VALUES (?, ?, ?)',
                 (short_code, long_url, title))
    conn.commit()
    conn.close()
    
    # Log activity
    activity_conn = get_db_connection(ACTIVITY_DB)
    activity_conn.execute('INSERT INTO activity (action, entity_type, entity_id, details) VALUES (?, ?, ?, ?)',
                         ('link_create', 'link', short_code, f'Created short link: {short_code} -> {long_url}'))
    activity_conn.commit()
    activity_conn.close()
    
    return jsonify({
        'success': True,
        'short_url': f"{request.url_root}r/{short_code}",
        'short_code': short_code,
        'original_url': long_url
    })

@app.route('/r/<code>')
def redirect_handler(code):
    conn = get_db_connection(LINKS_DB)
    link = conn.execute('SELECT long_url FROM links WHERE short_code = ?', (code,)).fetchone()
    
    if link:
        # Update click count and last access
        conn.execute('UPDATE links SET clicks = clicks + 1, last_access = ? WHERE short_code = ?',
                     (datetime.now().isoformat(), code))
        conn.commit()
        conn.close()
        
        return redirect(link['long_url'])
    else:
        conn.close()
        return "Short link not found", 404

# Page routes
@app.route('/upload')
def upload_page():
    return render_template('upload.html')

@app.route('/files')
def files_page():
    return render_template('files.html')

@app.route('/shortener')
def shortener_page():
    return render_template('shortener.html')

@app.route('/api')
def api_page():
    return render_template('api.html')

# Static file serving
@app.route('/assets/<path:filename>')
def assets(filename):
    return send_from_directory('assets', filename)

# Catch-all route for SPA behavior (404 handling)
@app.route('/<path:path>')
def catch_all(path):
    # If path is an API endpoint, don't redirect
    if path.startswith('api/') or path.startswith('r/'):
        return jsonify({'error': 'Endpoint not found'}), 404
    # Otherwise, serve the main index page for 404 handling
    return render_template('index.html')

if __name__ == '__main__':
    init_db()
    app.run(host='0.0.0.0', port=8000, debug=True)
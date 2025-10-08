import requests
import json
import os
import tempfile

# Base URL for the API (adjust if running elsewhere)
BASE_URL = "http://localhost:5000"

def test_api():
    print("Testing GitFile Hosting API...")
    
    # Test the home endpoint
    response = requests.get(BASE_URL)
    if response.status_code == 200:
        print("✓ Home endpoint works")
        print(f"Response: {response.json()}")
    else:
        print(f"✗ Home endpoint failed with status {response.status_code}")
    
    # Test listing files (should be empty initially)
    response = requests.get(f"{BASE_URL}/files")
    if response.status_code == 200:
        print(f"✓ Files endpoint works, current files: {len(response.json())}")
    else:
        print(f"✗ Files endpoint failed with status {response.status_code}")
    
    # Test the shortener (should be empty initially)
    response = requests.get(f"{BASE_URL}/links")
    if response.status_code == 200:
        print(f"✓ Links endpoint works, current links: {len(response.json())}")
    else:
        print(f"✗ Links endpoint failed with status {response.status_code}")
    
    # Test shortening a URL
    test_url = "https://www.example.com/very/long/url/for/testing/purposes"
    response = requests.post(f"{BASE_URL}/shorten", json={"url": test_url})
    if response.status_code == 201:
        print("✓ URL shortening works")
        result = response.json()
        print(f"Short URL: {result['short_url']}")
    else:
        print(f"✗ URL shortening failed with status {response.status_code}")
        if response.status_code != 400:  # 400 might be expected if no body
            print(f"Response: {response.text}")
    
    # Test file upload (create a temporary file)
    with tempfile.NamedTemporaryFile(mode='w', delete=False, suffix='.txt') as temp_file:
        temp_file.write("This is a test file for the GitFile Hosting service.")
        temp_filename = temp_file.name

    try:
        with open(temp_filename, 'rb') as f:
            files = {'file': ('test.txt', f, 'text/plain')}
            response = requests.post(f"{BASE_URL}/upload", files=files)
            
            if response.status_code == 201:
                print("✓ File upload works")
                result = response.json()
                print(f"File ID: {result['file_id']}")
            else:
                print(f"✗ File upload failed with status {response.status_code}")
                print(f"Response: {response.text}")
    finally:
        # Clean up the temporary file
        os.unlink(temp_filename)

    print("\nTest complete!")

if __name__ == "__main__":
    test_api()
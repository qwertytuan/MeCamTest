# Camera Hub - Security Camera Management System

A full-stack security camera management system with real-time streaming, motion/human detection, and recording capabilities.

## 🚀 Features

- **JWT Authentication** - Secure user authentication with role-based access control
- **Real-time Camera Streaming** - WebSocket-based live video streaming
- **AI Detection** - Motion and human detection using YOLO
- **Automatic Recording** - Triggered recordings on detection events
- **Recording Viewer** - View and download recorded videos with H.264 transcoding
- **Camera Sharing** - Generate time-limited share links for camera access
- **Admin Dashboard** - Manage cameras, users, and permissions

## 📋 Requirements

### Laravel Backend
- PHP 8.2+
- Composer
- MySQL 8.0+ / MariaDB 10.6+
- Node.js 18+ & npm

### Python Camera Server
- Python 3.12+
- FFmpeg (for video transcoding)
- UV package manager (recommended) or pip

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/qwertytuan/MeCamTest.git
cd MeCamTest
```

### 2. Laravel Setup

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Configure your database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=your_database
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run migrations and seed the database
php artisan migrate --seed

# Install frontend dependencies
npm install

# Build frontend assets
npm run build
```

### 3. Python Camera Server Setup

```bash
cd PythonCamConnect

# Using UV (recommended)
uv sync

# Or using pip
pip install -r requirements.txt

# Copy environment file
cp .env.example .env
```

### 4. FFmpeg Installation (Required for video playback)

```bash
# Ubuntu/Debian
sudo apt install ffmpeg

# macOS
brew install ffmpeg

# Windows - Download from https://ffmpeg.org/download.html
```

## 🚀 Running the Application

### Option 1: Run All Services (Development)

```bash
# In the project root, run Laravel with all services
composer dev
```

This starts:
- Laravel server (http://localhost:8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server

### Option 2: Run Services Separately

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Python Camera Server:**
```bash
cd PythonCamConnect
uv run main.py
# Or: python main.py
```

**Terminal 3 - Vite (for development):**
```bash
npm run dev
```

## 🔐 Default Login Credentials

After running `php artisan migrate --seed`, you can use these accounts:

| Role  | Email               | Password   |
|-------|---------------------|------------|
| Admin | admin@example.com   | password   |
| User  | user@example.com    | password   |
| Guest | guest@example.com   | password   |

## 📁 Project Structure

```
├── app/                    # Laravel application
│   ├── Http/Controllers/   # API controllers
│   ├── Models/             # Eloquent models
│   └── Http/Middleware/    # Custom middleware
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/            # Database seeders
├── resources/views/        # Blade templates
├── routes/
│   ├── api.php             # API routes
│   └── web.php             # Web routes
├── PythonCamConnect/       # Python camera server
│   ├── main.py             # Flask application
│   ├── utils/              # Camera & detection utilities
│   ├── storage/            # Recordings & thumbnails
│   └── static/             # WebSocket client JS
└── public/                 # Public assets
```

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Register
- `POST /api/auth/logout` - Logout
- `GET /api/auth/user-profile` - Get current user

### Cameras (Admin)
- `GET /api/admin/cameras` - List all cameras
- `POST /api/admin/cameras` - Create camera
- `PUT /api/admin/cameras/{id}` - Update camera
- `DELETE /api/admin/cameras/{id}` - Delete camera
- `POST /api/admin/cameras/init` - Initialize camera stream

### Recordings
- `GET /api/cameras/recordings` - Get all user recordings
- `GET /api/cameras/{id}/recordings` - Get camera recordings

### Python Camera Server
- `GET /api/camera/list` - List active streams
- `POST /api/camera/init` - Initialize camera
- `WS /stream/{stream_id}` - WebSocket video stream

## 🎥 Camera Configuration

### USB Camera
```json
{
  "name": "Office Camera",
  "location": "Main Office",
  "connection_type": "USB",
  "usb_path": "/dev/video0",
  "detection_type": "MOTION_HUMAN",
  "detection_enabled": true
}
```

### RTSP Stream
```json
{
  "name": "IP Camera",
  "location": "Entrance",
  "connection_type": "STREAM",
  "stream_url": "rtsp://192.168.1.100:554/stream",
  "stream_username": "admin",
  "stream_password": "password"
}
```

## 🔧 Configuration

### Environment Variables (.env)

```env
# Application
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=camera_hub
DB_USERNAME=root
DB_PASSWORD=

# JWT
JWT_SECRET=your-jwt-secret
JWT_TTL=60
```

### Python Camera Server (.env)

```env
FLASK_DEBUG=true
STORAGE_PATH=./storage
```

## 📝 License

MIT License

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

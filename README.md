# 🎬 Noobz Cinema

<p align="center">
    <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
    <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
    <img src="https://img.shields.io/badge/Redis-Cache-DC382D?style=for-the-badge&logo=redis&logoColor=white" alt="Redis">
    <img src="https://img.shields.io/badge/TMDB-API-01B4E4?style=for-the-badge&logo=themoviedatabase&logoColor=white" alt="TMDB">
</p>

<p align="center">
    <strong>A modern, secure, and feature-rich movie & TV series streaming platform built with Laravel 12.x</strong>
</p>

## ✨ Features

### 🎯 **Core Functionality**
- **Movie & Series Management** - Complete CRUD operations with TMDB integration
- **User Management** - Hierarchical role system with advanced permissions
- **Search & Filtering** - Full-text search with genre, year, and rating filters
- **Watchlist System** - Personal movie/series tracking for users
- **Analytics Dashboard** - Comprehensive viewing statistics and reports

### 🔐 **Security & Authentication**
- **Multi-level Role System** - `super_admin` → `admin` → `moderator` → `member` → `guest`
- **Advanced Authorization** - Hierarchical permission checks with `canManage()` methods
- **Security Headers** - CSRF, XSS, and clickjacking protection
- **Rate Limiting** - Granular throttling for different operations
- **Session Security** - Encrypted sessions with automatic regeneration

### 🚀 **Performance & Architecture**
- **Redis Caching** - Comprehensive caching strategy for optimal performance
- **Eager Loading** - N+1 query prevention with optimized database queries
- **Service Architecture** - Clean separation of business logic
- **API Layer** - RESTful API with standardized responses
- **Database Optimization** - Proper indexing and query optimization

### 🎨 **User Experience**
- **Responsive Design** - Modern UI with Tailwind CSS 4.x
- **Interactive Components** - Alpine.js 3.x for dynamic interactions
- **Real-time Updates** - Live search and filtering
- **Mobile Optimized** - Full mobile responsiveness

## 🛠️ **Technology Stack**

### **Backend**
- **Laravel 12.x** - PHP framework with latest features
- **PHP 8.2+** - Modern PHP with performance improvements
- **MySQL 8.0+** - Relational database with full-text search
- **Redis** - In-memory caching and session storage

### **Frontend**
- **Blade Templates** - Laravel's templating engine
- **Tailwind CSS 4.x** - Utility-first CSS framework
- **Alpine.js 3.x** - Lightweight JavaScript framework
- **Vite** - Fast build tool and asset compilation

### **External Services**
- **TMDB API** - Movie and TV show metadata
- **TMDB Images** - High-quality poster and backdrop images

### **Development Tools**
- **Laravel Pint** - Code style fixer
- **PHPUnit** - Unit and feature testing
- **Laravel Pail** - Real-time log monitoring
- **Composer** - PHP dependency management

## 📋 **Requirements**

- **PHP 8.2 or higher**
- **Composer 2.x**
- **Node.js 18+ & NPM**
- **MySQL 8.0 or higher**
- **Redis Server** (optional but recommended)
- **TMDB API Key** (free from [themoviedb.org](https://www.themoviedb.org/settings/api))

## 🚀 **Installation**

### **1. Clone the Repository**
```bash
git clone https://github.com/YOUR_USERNAME/noobz-cinema.git
cd noobz-cinema
```

### **2. Install Dependencies**
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### **3. Environment Setup**
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### **4. Configure Environment Variables**
Edit `.env` file with your configuration:

```env
# Application
APP_NAME="Noobz Cinema"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=noobz_cinema
DB_USERNAME=root
DB_PASSWORD=your_password

# Redis (Optional but recommended)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_STORE=redis

# TMDB API
TMDB_API_KEY=your_tmdb_api_key_here
TMDB_BASE_URL=https://api.themoviedb.org/3
TMDB_IMAGE_URL=https://image.tmdb.org/t/p

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

### **5. Database Setup**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE noobz_cinema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations and seeders
php artisan migrate --seed
```

### **6. Storage Setup**
```bash
# Create storage link
php artisan storage:link

# Set proper permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### **7. Build Assets**
```bash
# For development
npm run dev

# For production
npm run build
```

## 🏃‍♂️ **Running the Application**

### **Development Mode**
```bash
# Start all services (recommended)
composer run dev

# Or start individually
php artisan serve                    # Laravel server
npm run dev                         # Vite dev server
php artisan queue:listen --tries=1  # Queue worker
php artisan pail --timeout=0       # Real-time logs
```

### **Production Mode**
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Build production assets
npm run build

# Start queue worker
php artisan queue:work --daemon
```

## 👤 **Default Users**

After running seeders, you can login with:

| Role | Username | Email | Password |
|------|----------|-------|----------|
| Super Admin | `admin` | `admin@noobzcinema.com` | `admin123` |
| Member | `user` | `user@noobzcinema.com` | `password123` |

⚠️ **Important**: Change these passwords in production!

## 🔧 **Configuration**

### **TMDB API Setup**
1. Register at [themoviedb.org](https://www.themoviedb.org/signup)
2. Go to [API Settings](https://www.themoviedb.org/settings/api)
3. Request an API key
4. Add your API key to `.env` as `TMDB_API_KEY`

### **Redis Setup (Optional)**
```bash
# Install Redis (Ubuntu/Debian)
sudo apt update
sudo apt install redis-server

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Update .env
CACHE_STORE=redis
SESSION_DRIVER=redis
```

### **Queue Configuration**
```bash
# For background job processing
php artisan queue:work

# Or use supervisor for production
sudo apt install supervisor
```

## 🧪 **Testing**

```bash
# Run all tests
composer run test

# Run specific test types
php artisan test --filter=Unit
php artisan test --filter=Feature

# Run with coverage
php artisan test --coverage
```

## 📊 **Performance Optimization**

### **Caching Strategy**
- **Config Cache**: `php artisan config:cache`
- **Route Cache**: `php artisan route:cache`
- **View Cache**: `php artisan view:cache`
- **Redis Cache**: Automatic caching of TMDB API calls and database queries

### **Database Optimization**
- Full-text search indexes
- Proper foreign key relationships
- Query optimization with eager loading
- Pagination for large datasets

### **Asset Optimization**
- Vite for fast builds and hot reloading
- CSS and JS minification
- Image optimization for posters and backdrops

## 🔒 **Security Features**

### **OWASP Top 10 Compliance**
- ✅ **A01: Broken Access Control** - Hierarchical role system
- ✅ **A02: Cryptographic Failures** - Strong password hashing
- ✅ **A03: Injection** - Eloquent ORM + input validation
- ✅ **A04: Insecure Design** - Secure architecture patterns
- ✅ **A05: Security Misconfiguration** - Proper Laravel configuration
- ✅ **A06: Vulnerable Components** - Updated dependencies
- ✅ **A07: Authentication Failures** - Strong authentication system
- ✅ **A08: Software Integrity** - CSRF protection + validation
- ✅ **A09: Logging Failures** - Comprehensive logging system
- ✅ **A10: SSRF** - Input validation + API security

### **Additional Security Measures**
- Rate limiting on sensitive endpoints
- Session encryption and regeneration
- XSS protection with auto-escaping
- SQL injection prevention
- Security headers implementation

## 🏗️ **Architecture Overview**

### **Directory Structure**
```
app/
├── Http/
│   ├── Controllers/           # Request handling
│   │   ├── Admin/            # Admin panel controllers
│   │   └── Api/              # API endpoints
│   ├── Requests/             # Form validation
│   └── Middleware/           # Custom middleware
├── Models/                   # Eloquent models
├── Services/                 # Business logic layer
│   ├── Admin/               # Admin-specific services
│   └── TMDB/                # TMDB API integration
├── Traits/                  # Reusable traits
└── Exceptions/              # Custom exceptions

resources/
├── views/                   # Blade templates
│   ├── admin/              # Admin panel views
│   ├── auth/               # Authentication views
│   └── layouts/            # Layout templates
├── css/                    # Stylesheets
└── js/                     # JavaScript files

database/
├── migrations/             # Database migrations
├── seeders/               # Database seeders
└── factories/             # Model factories
```

### **Design Patterns Used**
- **Repository Pattern** - Data access abstraction
- **Service Layer** - Business logic separation
- **Observer Pattern** - Model events and listeners
- **Factory Pattern** - Object creation
- **Trait Pattern** - Code reusability

## 📚 **API Documentation**

### **Authentication**
All API endpoints require authentication via Laravel Sanctum tokens.

### **Available Endpoints**

#### **Movies**
```http
GET    /api/movies              # List movies
GET    /api/movies/{id}         # Get movie details
POST   /api/movies              # Create movie (admin only)
PUT    /api/movies/{id}         # Update movie (admin only)
DELETE /api/movies/{id}         # Delete movie (admin only)
```

#### **Series**
```http
GET    /api/series              # List series
GET    /api/series/{id}         # Get series details
POST   /api/series              # Create series (admin only)
PUT    /api/series/{id}         # Update series (admin only)
DELETE /api/series/{id}         # Delete series (admin only)
```

#### **User Management**
```http
GET    /api/admin/users         # List users (admin only)
PUT    /api/admin/users/{id}/role      # Update user role (admin only)
PUT    /api/admin/users/{id}/permissions # Update permissions (admin only)
```

## 🤝 **Contributing**

We welcome contributions! Please follow these steps:

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Make your changes** and add tests
4. **Run tests**: `composer run test`
5. **Run code style check**: `./vendor/bin/pint`
6. **Commit changes**: `git commit -m 'Add amazing feature'`
7. **Push to branch**: `git push origin feature/amazing-feature`
8. **Open a Pull Request**

### **Code Style Guidelines**
- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting
- Write comprehensive tests for new features
- Document complex functionality

## 📄 **License**

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 **Authors & Acknowledgments**

- **Developer**: Built with care for the cinema community
- **TMDB**: Movie and TV show data provided by The Movie Database
- **Laravel Community**: For the amazing framework and ecosystem
- **Open Source Contributors**: For tools and packages used

## 🐛 **Bug Reports & Feature Requests**

Please use the [GitHub Issues](https://github.com/YOUR_USERNAME/noobz-cinema/issues) page to:
- Report bugs
- Request new features
- Ask questions
- Provide feedback

## 📞 **Support**

- **Documentation**: Check this README and code comments
- **Issues**: [GitHub Issues](https://github.com/YOUR_USERNAME/noobz-cinema/issues)
- **Discussions**: [GitHub Discussions](https://github.com/YOUR_USERNAME/noobz-cinema/discussions)

## 🔮 **Roadmap**

### **Upcoming Features**
- [ ] **User Profiles** - Detailed user profiles with viewing history
- [ ] **Recommendation Engine** - AI-powered content recommendations
- [ ] **Advanced Search** - Filters by cast, crew, and advanced criteria
- [ ] **Multi-language Support** - Internationalization (i18n)
- [ ] **Mobile App** - React Native or Flutter mobile application
- [ ] **Social Features** - Reviews, ratings, and social sharing
- [ ] **Streaming Integration** - Direct streaming capabilities
- [ ] **Download Management** - Offline viewing support

### **Technical Improvements**
- [ ] **Elasticsearch** - Advanced search capabilities
- [ ] **Docker Support** - Containerized deployment
- [ ] **CI/CD Pipeline** - Automated testing and deployment
- [ ] **API Rate Limiting** - Advanced rate limiting strategies
- [ ] **Microservices** - Service separation for scalability

---

<p align="center">
    <strong>Built with ❤️ for movie and TV show enthusiasts</strong>
</p>

<p align="center">
    <a href="#-features">Features</a> •
    <a href="#-installation">Installation</a> •
    <a href="#-api-documentation">API</a> •
    <a href="#-contributing">Contributing</a> •
    <a href="#-license">License</a>
</p>
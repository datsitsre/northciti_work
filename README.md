# News Platform

A comprehensive REST API-driven platform for managing news articles and events with user engagement features.

## Features

-   📰 **News Management**: Create, publish, and manage news articles
-   📅 **Event Management**: Organize and promote events
-   👥 **User System**: Registration, authentication, and profiles
-   💬 **Comments & Engagement**: Interactive comment system with voting
-   🔍 **Search & Filtering**: Advanced search capabilities
-   📱 **Responsive Design**: Mobile-first approach
-   🔐 **Security**: JWT authentication, rate limiting, input validation
-   📊 **Analytics**: Comprehensive analytics and reporting
-   🎛️ **Admin Dashboard**: Full administrative control panel

## Architecture

-   **API**: Modern PHP 8+ REST API with modular architecture
-   **Admin**: Web-based administrative dashboard
-   **Users**: Public-facing user interface
-   **Database**: MySQL with optimized schema and indexing

## Installation

1. **Clone the repository**

    ```bash
    git clone <repository-url>
    cd news-platform
    ```

2. **Install dependencies**

    ```bash
    composer install
    ```

3. **Environment setup**

    ```bash
    cp .env.example .env
    # Edit .env with your configuration
    ```

4. **Database setup**

    ```bash
    mysql -u root -p < database.sql
    ```

5. **Set permissions**
    ```bash
    chmod -R 755 storage/
    ```

## Configuration

Edit the `.env` file with your settings:

-   Database credentials
-   JWT secret key
-   Email configuration
-   OAuth settings
-   File upload settings

## API Endpoints

### Authentication

-   `POST /api/auth/login` - User login
-   `POST /api/auth/register` - User registration
-   `POST /api/auth/refresh` - Refresh JWT token

### News

-   `GET /api/news` - List news articles
-   `GET /api/news/{id}` - Get single article
-   `POST /api/news` - Create article (contributor)
-   `PUT /api/news/{id}` - Update article

### Events

-   `GET /api/events` - List events
-   `GET /api/events/{id}` - Get single event
-   `POST /api/events` - Create event (contributor)
-   `PUT /api/events/{id}` - Update event

### Admin

-   `GET /api/admin/dashboard` - Dashboard stats
-   `GET /api/admin/users` - User management
-   `PUT /api/admin/moderation/{id}` - Content moderation

## Development

### Running Tests

```bash
composer test
```

### Code Analysis

```bash
composer analyse
```

### API Documentation

API documentation is available at `/api/docs/` after setup.

## Deployment

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Configure your web server to point to the project root
4. Set up SSL certificates
5. Configure cron jobs for email queue processing

## Security

-   JWT token authentication
-   Rate limiting per IP/API key
-   Input validation and sanitization
-   SQL injection protection
-   XSS protection
-   CSRF protection
-   File upload security

## Support

For support and documentation, please refer to:

-   API Documentation: `/api/docs/`
-   Project Issues: Create an issue in the repository

## License

This project is licensed under the MIT License.

group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300

focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105

prompt
now lets create the media page for the admin dashboard,
look through recent chats in the project for knowledge

group inline-flex items-center justify-center px-4 py-1.5 text-gray-700 bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-2xl hover:bg-white hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-gray-300/50 transition-all duration-300 transform hover:scale-105 font-semibold w-full sm:w-auto

I have provided all necessary files including API endpoints and database structure.

Now lets create the user interface.

This will be component based where navbar and footer and other key components will be reusable. Note dont use react.. just vanila js/jquery/alpine, css, tailwind.

All post and update request must go through a backend controller before to api.

I want a very modern UI/UX design, with a touch of #49789f, #e6c4e7, #dac9f3, #2c9ca7 just vanila js/jquery/alpine, css, tailwind.

Make sure we have mechanism for darkmode and light mode support.

It should be absolutely responsive.

Lets currently, just work on the layout and base files, including the backend mechanisms.

Then lets work on the navbars and footers and little components.

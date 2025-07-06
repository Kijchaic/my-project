# Search Template System

A reusable search template system that can be used for multiple product types (eSIM, Hotels, Flights, Cars) with secure database connections and organized code structure.

## Features

- **Modular Design**: Separate CSS, JavaScript, and PHP files for better organization
- **Secure Database**: PDO-based database connection with prepared statements
- **Template System**: Reusable search functionality for different product types
- **Responsive UI**: Modern, responsive design with Tailwind CSS
- **AJAX Search**: Real-time search with debouncing
- **Analytics**: Search logging for user behavior analysis

## File Structure

```
├── config/
│   └── database.php          # Secure database configuration
├── includes/
│   └── SearchTemplate.php    # Reusable search template class
├── styles/
│   └── main.css             # Separated CSS styles
├── js/
│   └── search.js            # JavaScript search functionality
├── api/
│   └── search.php           # AJAX search endpoint
├── esim-search.php          # eSIM search page example
├── hotel-search.php         # Hotel search page example
├── setup.php                # Database setup script
└── README.md               # This file
```

## Installation

1. **Database Setup**:
   - Configure your database settings in `config/database.php`
   - Run `setup.php` to create tables and insert sample data

2. **Environment Variables** (Optional):
   ```bash
   DB_HOST=localhost
   DB_NAME=search_products
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

3. **Web Server**:
   - Place files in your web server directory
   - Ensure PHP has PDO and MySQL extensions enabled

## Usage

### Basic Usage

```php
<?php
require_once 'includes/SearchTemplate.php';

// Create search instance for eSIM
$search = new SearchTemplate('esim');

// Set search parameters
$search->setSearchTerm('Global');
$search->setFilters(['max_price' => 30, 'countries' => ['USA', 'UK']]);

// Get results
$results = $search->searchProducts();

// Render interface
echo $search->renderSearchInterface();
?>
```

### Supported Product Types

- **eSIM**: `new SearchTemplate('esim')`
- **Hotel**: `new SearchTemplate('hotel')`
- **Flight**: `new SearchTemplate('flight')`
- **Car**: `new SearchTemplate('car')`

### Customization

#### Adding New Product Types

1. Add configuration in `SearchTemplate.php`:
```php
'new_product' => [
    'title' => 'New Product Search',
    'subtitle' => 'Find the perfect new product',
    'filters' => ['custom_filter'],
    'price_range' => [0, 100],
    'icon' => 'custom-icon',
    'theme' => 'theme-new-product'
]
```

2. Add CSS theme in `styles/main.css`:
```css
.theme-new-product {
    --primary-gradient: linear-gradient(135deg, #your-color, #your-color);
    --accent-color: #your-color;
    --bg-gradient: linear-gradient(to-br, #your-bg, #your-bg);
}
```

#### Custom Filters

Extend the `SearchTemplate` class to add custom filters:

```php
class CustomSearchTemplate extends SearchTemplate {
    public function setCustomFilter($value) {
        $this->filters['custom_filter'] = $value;
        return $this;
    }
}
```

## Database Schema

### Products Table
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('esim', 'hotel', 'flight', 'car') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    data_gb INT DEFAULT NULL,
    days INT DEFAULT NULL,
    countries JSON DEFAULT NULL,
    description TEXT,
    image_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Search Logs Table
```sql
CREATE TABLE search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_term VARCHAR(255),
    filters JSON,
    product_type VARCHAR(50),
    results_count INT,
    user_ip VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Security Features

- **Prepared Statements**: All database queries use prepared statements
- **Input Validation**: Proper validation of all user inputs
- **Error Handling**: Secure error handling without exposing sensitive information
- **CORS Headers**: Proper CORS configuration for API endpoints
- **SQL Injection Protection**: PDO with prepared statements prevents SQL injection

## API Endpoints

### Search API
- **URL**: `/api/search.php`
- **Method**: POST
- **Content-Type**: application/json

**Request Body**:
```json
{
    "search_term": "search text",
    "filters": {
        "max_price": 50,
        "countries": ["USA", "UK"],
        "max_data": 10,
        "max_days": 30
    }
}
```

**Response**:
```json
{
    "success": true,
    "results": [...],
    "count": 5,
    "product_type": "esim"
}
```

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Dependencies

- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- PDO PHP Extension
- MySQL PHP Extension

## License

This project is open source and available under the MIT License.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Support

For support and questions, please create an issue in the repository. 
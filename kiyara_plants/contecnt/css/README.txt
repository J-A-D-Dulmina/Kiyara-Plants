# Kiyara Plants CSS Structure

## Admin Styling

All admin styles have been consolidated in the `admin_style.css` file. This file includes:

1. Common layouts (sidebar, content areas)
2. Component styles (cards, tables, forms)
3. UI elements (buttons, badges, alerts)
4. Dashboard-specific components
5. Responsive adjustments

### How to Use

When creating a new admin page, only include the main admin_style.css file:

```html
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<!-- Admin Styles -->
<link rel="stylesheet" href="contecnt/css/admin_style.css">
```

### Available Components

The admin_style.css includes these reusable components:

- Sidebar navigation
- Content layout
- Data tables and product tables
- Status badges for orders and products
- Product cards and grids
- Form containers and elements
- Dashboard stats cards
- Quick action cards
- Common utilities

### Extending Styles

If you need to add very specific styles for a new admin page that won't be reused elsewhere,
consider adding them directly into admin_style.css in a clearly marked section.

For example:

```css
/* ===== REPORTS PAGE SPECIFIC STYLES ===== */
.chart-container {
    height: 400px;
    margin-bottom: 30px;
}
```

### Color Variables

Use CSS variables for consistent colors:

```css
:root {
    --primary-color: #4caf50;
    --secondary-color: #388e3c;
    --light-color: #f1f8e9;
    --dark-color: #F5F5F5;
    --text-color: #333;
    --gray-color: #f5f5f5;
    ...
}
```

Refer to the variables section at the top of admin_style.css for all available variables. 
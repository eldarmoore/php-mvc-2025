<?php $this->extend('layouts.app'); ?>

<?php $this->section('title'); ?>
Welcome to MVC Framework
<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
.welcome-hero {
    text-align: center;
    padding: 3rem 0;
}

.welcome-hero h1 {
    font-size: 3rem;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.welcome-hero p {
    font-size: 1.2rem;
    color: #7f8c8d;
    margin-bottom: 2rem;
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.feature {
    padding: 1.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.feature:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.feature h3 {
    color: #3498db;
    margin-bottom: 0.5rem;
}

.feature p {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.cta {
    text-align: center;
    margin-top: 3rem;
}

.btn {
    display: inline-block;
    padding: 0.75rem 2rem;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s;
}

.btn:hover {
    background: #2980b9;
}
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="welcome-hero">
    <h1>Welcome to Your Custom MVC Framework!</h1>
    <p>A powerful, lightweight PHP framework built from scratch</p>
</div>

<div class="features">
    <div class="feature">
        <h3>🚀 Routing System</h3>
        <p>Flexible routing with support for dynamic parameters, HTTP methods, and middleware</p>
    </div>

    <div class="feature">
        <h3>🎨 Template Engine</h3>
        <p>Clean template syntax with layouts, sections, and partials support</p>
    </div>

    <div class="feature">
        <h3>💉 Dependency Injection</h3>
        <p>Powerful IoC container with automatic dependency resolution</p>
    </div>

    <div class="feature">
        <h3>🗄️ Database Layer</h3>
        <p>PDO-based database abstraction with query builder and ORM</p>
    </div>

    <div class="feature">
        <h3>🔒 Security</h3>
        <p>Built-in CSRF protection, XSS prevention, and password hashing</p>
    </div>

    <div class="feature">
        <h3>✅ Validation</h3>
        <p>Comprehensive data validation system with custom rules</p>
    </div>
</div>

<div class="cta">
    <a href="/hello" class="btn">Get Started</a>
</div>
<?php $this->endSection(); ?>

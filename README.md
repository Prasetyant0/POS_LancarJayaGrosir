/*
PROJECT CONTEXT: POS (Point of Sale) untuk Toko Grosir
- Framework: Laravel 9.5.2
- PHP Version: 8.0
- Frontend Interactivity: Livewire 2.12 (tanpa Alpine.js tambahan)
- Database: MySQL
- Authentication: Laravel Breeze + Livewire stack
- Role-based: 'admin', 'kasir'

DATABASE SCHEMA (sudah di-migrate):
- users (id, name, email, password, role)
- customers (id, customer_code, name, contact_person, phone, email, address, credit_limit)
- categories (id, name)
- products (id, product_code, name, category_id, purchase_price, wholesale_price, retail_price, current_stock, unit, is_active)
- sales (id, invoice_number, customer_id, user_id, total_amount, discount, final_amount, paid_amount, change_amount, payment_status, due_date, status, notes)
- sale_details (id, sale_id, product_id, quantity, unit_price, total_price)
- purchases & purchase_details (untuk stok masuk)

CONVENTIONS:
- Gunakan Eloquent ORM dengan relasi yang benar.
- Semua harga dalam DECIMAL(15,2).
- Jangan gunakan softDeletes kecuali diminta.
- Untuk Livewire: gunakan mount(), render(), dan action methods (store, update, destroy).
- Validasi input di Livewire component (bukan di controller).
- Update stok produk secara real-time saat ada penjualan/pembelian.
- Format invoice_number: INV-YYYYMMDD-001 (contoh: INV-20251028-001)

Contoh struktur Livewire:
- Class: app/Http/Livewire/Sales/CreateSale.php
- View: resources/views/livewire/sales/create-sale.blade.php

JANGAN gunakan JavaScript vanilla berlebihan — prioritaskan Livewire.
*/

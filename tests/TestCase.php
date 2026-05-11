<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base TestCase untuk seluruh sesi pengujian.
 *
 * RefreshDatabase memastikan setiap test mendapat database yang bersih.
 * Karena kita menggunakan SQLite :memory:, migration dijalankan ulang
 * di awal setiap test class — sangat cepat dan tidak merusak data produksi.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
}

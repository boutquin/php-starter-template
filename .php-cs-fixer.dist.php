<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/**
 * PHP-CS-Fixer configuration file.
 *
 * Purpose:
 * Applies a strict, modern rule set for PHP 8.3 projects using PSR-12,
 * migration rules, and additional style/consistency rules.
 *
 * Features:
 * - Applies to src/ and tests/ directories
 * - Enables risky and migration-level rules
 * - Honors strict typing and unused import cleanup
 *
 * Usage:
 * Run via Composer scripts:
 *   composer fix      # Applies fixes
 *   composer fix:dry  # Shows fixable issues without applying
 *
 * @author
 *      Pierre G. Boutquin <github.com/boutquin>
 * @license
 *      Apache-2.0
 * @see
 *      https://github.com/FriendsOfPHP/PHP-CS-Fixer
 */

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->name('*.php');

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        // Base rule sets
        '@PSR12' => true,
        '@PHP81Migration' => true,
        '@PHP83Migration' => true,

        // Language-level strictness
        'strict_param' => true,
        'declare_strict_types' => true,

        // Syntax preferences
        'array_syntax' => ['syntax' => 'short'],
        'function_declaration' => ['closure_function_spacing' => 'none'],
        'single_line_throw' => false,

        // Import management
        'ordered_imports' => true,
        'no_unused_imports' => true,

        // Docblock conventions
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_separation' => true,
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_order' => true,
        'phpdoc_var_without_name' => false,
        'no_blank_lines_after_phpdoc' => true,

        // Visibility requirements
        'visibility_required' => [
            'elements' => ['const', 'property', 'method'],
        ],
    ])
    ->setFinder($finder);

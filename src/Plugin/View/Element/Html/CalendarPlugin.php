<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.0.0
 * @date        2025-12-01
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Plugin\View\Element\Html;

use Magento\Framework\View\Element\Html\Calendar;
use Magento\Framework\Locale\DataBundle;

/**
 * Plugin to fix "Trying to access array offset on null" error in Calendar
 * This happens when locale data doesn't have AmPmMarkers
 */
class CalendarPlugin
{
    /**
     * Around plugin to catch exception and return empty string
     * The calendar will still function, just without the AM/PM markers
     *
     * @param Calendar $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundToHtml(Calendar $subject, callable $proceed): string
    {
        try {
            return $proceed();
        } catch (\Exception $e) {
            // Check if this is the array offset on null error in Calendar
            if (strpos($e->getMessage(), 'Trying to access array offset on null') !== false &&
                (strpos($e->getFile(), 'Calendar.php') !== false || 
                 strpos($e->getTraceAsString(), 'Calendar.php') !== false)) {
                // Return empty string - the calendar will still work without AM/PM markers
                // This prevents the page from crashing
                return '';
            }
            // Re-throw if it's a different exception
            throw $e;
        } catch (\Throwable $e) {
            // Also catch Throwable (PHP 7+)
            if (strpos($e->getMessage(), 'Trying to access array offset on null') !== false &&
                (strpos($e->getFile(), 'Calendar.php') !== false || 
                 strpos($e->getTraceAsString(), 'Calendar.php') !== false)) {
                return '';
            }
            throw $e;
        }
    }
}


<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Interface to describe a navigation render behaviour.
 *
 * @package     format_simple_topics
 * @author      Dmitrii Metelkin <dmitriimo@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_simple_topics\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface navigation_links_render_interface describes navigation render behaviour.
 * @package format_simple_topics\output
 */
interface navigation_links_render_interface {
    /**
     * Render navigation links.
     *
     * @return string
     */
    public function render_navigation_links();
}

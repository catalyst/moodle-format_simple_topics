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
 * Base class for navigation links renderers.
 *
 * @package    format
 * @subpackage simple_topics
 * @author     Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_simple_topics\output;

use renderer_base;
use moodle_url;
use course_modinfo;

defined('MOODLE_INTERNAL') || die();

abstract class navigation_links_render_base extends renderer_base implements navigation_links_render_interface {
    /** @var course_modinfo */
    protected $modinfo;

    /**
     * Render previous link HTML.
     *
     * @param \moodle_url $url
     * @param string $text
     *
     * @return string
     */
    protected function make_previous_link_html(moodle_url $url, $text) {
        return $this->render_from_template('format_simple_topics/previous_link', [
            'url' => $url->out(),
            'text' => $text,
        ]);
    }

    /**
     * Render next link HTML.
     *
     * @param \moodle_url $url
     * @param string $text
     *
     * @return string
     */
    protected function make_next_link_html(moodle_url $url, $text) {
        return $this->render_from_template('format_simple_topics/next_link', [
            'url' => $url->out(),
            'text' => $text,
        ]);

    }

    /**
     * Return course_modinfo.
     *
     * @return \course_modinfo|null
     */
    protected function get_modinfo() {
        if (!isset($this->modinfo)) {
            $this->modinfo = get_fast_modinfo($this->get_course());
        }

        return $this->modinfo;
    }

    /**
     * Return a list of sections with their course module ids.
     *
     * @return array
     */
    protected function get_sections() {
        return $this->get_modinfo()->get_sections();
    }

    /**
     * Get sections info object.
     *
     * @return \section_info[]
     */
    protected function get_sections_info() {
        return $this->get_modinfo()->get_section_info_all();
    }

    /**
     * Return course object.
     *
     * @return \stdClass
     */
    protected function get_course() {
        return $this->page->course;
    }

    /**
     * Get course URL.
     *
     * @return \moodle_url
     */
    protected function get_course_url() {
        return new moodle_url('/course/view.php', ['id' => $this->get_course()->id]);
    }

    /**
     * Render previous link to a course.
     *
     * @return string
     */
    protected function make_previous_link_to_course() {
        return $this->make_previous_link_html($this->get_course_url(), $this->get_course()->shortname);
    }

    /**
     * Render next link to a dashboard.
     *
     * @return string
     */
    protected function make_next_link_to_dashboard() {
        return $this->make_next_link_html(new moodle_url('/my/'), get_string('myhome'));
    }

    /**
     * Render previous link to a dashboard.
     *
     * @return string
     */
    protected function make_previous_link_to_dashboard() {
        return $this->make_previous_link_html(new moodle_url('/my/'), get_string('myhome'));
    }

}

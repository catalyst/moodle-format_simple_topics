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
 * Render for activity level navigation links.
 *
 * @package    format
 * @subpackage simple_topics
 * @author     Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_simple_topics\output;

use cm_info;
use format_simple_topics\section_progress;
use html_writer;
use moodle_page;
use coding_exception;

defined('MOODLE_INTERNAL') || die();


class navigation_links_activity_renderer extends navigation_links_render_base {
    /** @var cm_info */
    protected $coursemodule;

    /**
     * Constructor.
     *
     * @param \moodle_page $page
     * @param string $target
     *
     * @throws \coding_exception is course module is not set for this page.
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        if (is_null($this->page->cm)) {
            throw new coding_exception('Course module is not set on this page!');
        }

        $this->coursemodule = $this->page->cm;
    }

    /**
     * @inheritdoc
     */
    public function render_navigation_links() {
        list($previouscm, $nextcm) = $this->get_previous_next_course_module();

        $previous = $this->make_previous_link($previouscm);
        $next = $this->make_next_link($nextcm);

        $links = $previous . $next;
        return html_writer::div($links, 'format_simple_topics-bottom-links');
    }

    /**
     * Get previous and next course modules
     * @return array
     */
    protected function get_previous_next_course_module() {
        $cms = $this->get_sections()[$this->coursemodule->sectionnum];

        $previous = null;
        $next = null;
        $found = false;

        foreach ($cms as $cmid) {
            if ($this->coursemodule->id == $cmid) {
                $found = true;
                continue;
            }
            $cm = $this->get_modinfo()->cms[$cmid];
            if (!$cm->uservisible) {
                continue;
            }
            if (!$found) {
                $previous = $cm;
            } else {
                $next = $cm;
                break;
            }
        }

        return [$previous, $next];
    }

    /**
     * Make previous link.
     *
     * @param mixed $previouscm Course module to point to or null.
     *
     * @return string
     */
    protected function make_previous_link($previouscm) {
        // Link to previous course module in the current section.
        if ($previouscm) {
            return $this->make_previous_link_html($previouscm->url, $previouscm->name);
        }

        // Get the last activity in the prev section.
        $prevactivity = $this->get_prev_section_last_cm($this->coursemodule->sectionnum);

        if ($prevactivity) {
            return $this->make_previous_link_html(
                $prevactivity->url,
                $prevactivity->name
            );
        }

        // Link to course if there is no prev activity.
        return $this->make_previous_link_to_course();
    }

    /**
     * Return the last activity of the prev section.
     *
     * @param int $currentsectionnum A number of the current section.
     *
     * @return mixed|null
     */
    protected function get_prev_section_last_cm($currentsectionnum) {
        $prevsectionnum = $currentsectionnum - 1;

        // We are not considering section 0, because it's not displayed anywhere.
        if ($prevsectionnum > 0) {
            $prevsection = $this->get_sections_info()[$prevsectionnum];
            $progress = new section_progress($prevsection);
            $lastactivity = $progress->get_last_activity();

            if ($lastactivity && $lastactivity->url instanceof \moodle_url) {
                return $lastactivity;
            } else {
                return $this->get_prev_section_last_cm($prevsectionnum);
            }
        }

        return null;
    }

    /**
     * Make next link.
     *
     * @param mixed $nextcm Course module to point to or null.
     *
     * @return string
     */
    protected function make_next_link($nextcm) {
        // Link to next course module.
        if ($nextcm) {
            return $this->make_next_link_html($nextcm->url, $nextcm->name);
        }

        // Get the first activity in the next section.
        $nextactivity = $this->get_next_section_first_cm($this->coursemodule->sectionnum);

        if ($nextactivity) {
            return $this->make_next_link_html(
                $nextactivity->url,
                $nextactivity->name
            );
        }

        return '';
    }

    /**
     * Return a first activity of the next section.
     *
     * @param int $currentsectionnum A number of the current section.
     *
     * @return mixed|null
     */
    protected function get_next_section_first_cm($currentsectionnum) {
        $nextsectionnum = $currentsectionnum + 1;

        // We are not considering section 0, because it's not displayed anywhere.
        if ($nextsectionnum < count($this->get_sections_info())) {
            $nextection = $this->get_sections_info()[$nextsectionnum];
            $progress = new section_progress($nextection);
            $firstactivity = $progress->get_first_activity();

            if ($firstactivity && !empty($firstactivity->url)) {
                return $firstactivity;
            } else {
                return $this->get_next_section_first_cm($nextsectionnum);
            }
        }

        return null;
    }
}

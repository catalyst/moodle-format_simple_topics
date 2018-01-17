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
 * Main class for the course format
 *
 * @package    format
 * @subpackage simple_topics
 * @author     Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/course/format/topics/lib.php');

class format_simple_topics extends format_topics {
    /**
     * Locally cached mod info object.
     *
     * @var course_modinfo
     */
    protected $modinfo;

    /**
     * Return course_modinfo.
     *
     * @return \course_modinfo|null
     * @throws \moodle_exception
     */
    protected function get_modinfo() {
        if (!isset($this->modinfo)) {
            $this->modinfo = get_fast_modinfo($this->get_course());
        }

        return $this->modinfo;
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        $sr = null;

        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }

        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }

        if (!is_null($sectionno)) {
            if (!empty($sr)) {
                $sectionno = $sr;
            }

            $firstactiviturl = $this->get_first_activity_url($sectionno);

            if (!empty($firstactiviturl) && $firstactiviturl instanceof moodle_url) {
                $url = $firstactiviturl;
            }

        }

        return $url;
    }

    /**
     * Loads all of the course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     *
     * @return array|void
     * @throws \moodle_exception
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        parent::extend_course_navigation($navigation, $node);

        foreach ($this->get_sections() as $section) {
            $progress = new \format_simple_topics\section_progress($section);

            // Remove general section and all empty sections form the navigation.
            if ($section->section == 0 || empty($progress->get_activities())) {
                $sectionnode = $node->get($section->id, navigation_node::TYPE_SECTION);
                if ($sectionnode) {
                    $sectionnode->remove();
                }
            }
        }
    }

    /**
     * Return first activity URL for the section.
     *
     * @param $sectionno
     *
     * @return \moodle_url|string
     * @throws \moodle_exception
     */
    protected function get_first_activity_url($sectionno) {
        $sections = $this->get_modinfo()->get_sections();

        if ($sections && !empty($sections[$sectionno])) {
            foreach ($sections[$sectionno] as $cmid) {
                $cm = $this->get_modinfo()->cms[$cmid];
                if ($cm->uservisible) {
                    return $cm->url;
                }
            }
        }

        return '';
    }

    /**
     * Course-specific information to be output immediately below content on any course page
     *
     * @return null|renderable null for no output or object with data for plugin renderer
     */
    public function course_content_footer() {
        global $PAGE;

        $links = null;

        // If we are on the course module view page.
        if ($PAGE->context && $PAGE->context->contextlevel == CONTEXT_MODULE && $PAGE->cm) {
            $links = new navigation_links();
        }

        return $links;
    }
}

/**
 * Class navigation_links is renderable for displaying next-prev links. It's required by format API.
 */
class navigation_links implements renderable {
    protected $currentsection = null;

    public function __construct($section = null) {
        $this->currentsection = $section;
    }

    public function get_current_section() {
        return $this->currentsection;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 * @throws \dml_exception
 */
function format_simple_topics_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'simple_topics'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

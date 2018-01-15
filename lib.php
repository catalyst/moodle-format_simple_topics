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
     * Definitions of the additional options that this course format uses for course
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;

        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),
                'coursedisplay' => array(
                    'default' => COURSE_DISPLAY_MULTIPAGE,
                    'type' => PARAM_INT,
                ),
            );
        }

        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseformatoptionsedit = array(
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }

        return $courseformatoptions;
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

            $url = $this->get_first_activity_url($sectionno);

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

        // We want to remove the general section from the navigation.
        $modinfo = $this->get_modinfo();
        $section = $modinfo->get_section_info(0);
        $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
        if ($generalsection) {
            // We found the node - now remove it.
            $generalsection->remove();
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
                if ($cm->available) {
                    return $cm->url;
                }
            }
        }

        return '';
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

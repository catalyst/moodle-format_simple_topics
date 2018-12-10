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
 * Section progress class.
 *
 * @package    format
 * @subpackage simple_topics
 * @author     Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_simple_topics;

use completion_info;
use section_info;

defined('MOODLE_INTERNAL') || die();

class section_progress {
    /**
     * Incomplete status.
     */
    const STATUS_INCOMPLETE = 0;

    /**
     * Complete status.
     */
    const STATUS_COMPLETE = 1;

    /**
     * Section object.
     *
     * @var \section_info
     */
    protected $section;

    /**
     * Course object.
     *
     * @var \stdClass
     */
    protected $course;

    /**
     * A list of all activities in the section.
     *
     * @var array
     */
    protected $activities;

    /** Section completion status.
     *
     * @var int
     */
    protected $status;

    /**
     * Course completion object.
     *
     * @var completion_info
     */
    protected $coursecompletion;

    /**
     * Constructor.
     *
     * @param \section_info $section
     */
    public function __construct(section_info $section) {
        $this->section = $section;
        $this->course = $this->section->modinfo->get_course();
    }

    /**
     * Return modinfo object.
     *
     * @return \course_modinfo
     */
    protected function get_modinfo() {
        return $this->section->modinfo;
    }

    /**
     * Return section number.
     *
     * @return int
     */
    public function get_sectionno() {
        return $this->section->section;
    }

    /**
     * Return a list of all activities in the section.
     *
     * @return array
     */
    public function get_activities() {
        if (!isset($this->activities)) {
            $this->activities = [];

            $sections = $this->get_modinfo()->get_sections();

            if ($sections && !empty($sections[$this->get_sectionno()])) {
                foreach ($sections[$this->get_sectionno()] as $cmid) {
                    $cm = $this->get_modinfo()->cms[$cmid];
                    if ($this->is_activity_valid($cm)) {
                        $this->activities[$cmid] = $cm;
                    }
                }
            }
        }

        return $this->activities;
    }

    /**
     * Return completion info object for the course.
     *
     * @return \completion_info
     */
    public function get_course_completion() {
        if (!isset($this->coursecompletion)) {
            $this->coursecompletion = new completion_info($this->course);
        }

        return $this->coursecompletion;
    }

    /**
     * Return the first activity in the section.
     *
     * @return mixed|null
     */
    public function get_first_activity() {
        if (!empty($this->get_activities())) {
            $activities = $this->get_activities();
            return reset($activities);
        } else {
            return null;

        }
    }

    /**
     * Return the first activity in the section.
     *
     * @return mixed|null
     */
    public function get_last_activity() {
        if (!empty($this->get_activities())) {
            $activities = $this->get_activities();
            return end($activities);
        } else {
            return null;
        }
    }

    /**
     * Return a number of activities in the section.
     *
     * @return int
     */
    public function count_activities() {
        return count($this->get_activities());
    }

    /**
     * Check if the section is completed.
     *
     * @return bool
     */
    public function is_completed() {
        return ($this->get_completion_status() == self::STATUS_COMPLETE);
    }

    /**
     * Return a section completion status.
     *
     * @return int
     */
    public function get_completion_status() {
        if (!isset($this->status)) {
            $this->status = self::STATUS_INCOMPLETE;
            $trackedactivities = $this->get_course_completion()->get_activities();

            foreach ($this->get_activities() as $cmid => $activity) {
                if (array_key_exists($activity->id, $trackedactivities)) {
                    if ($this->is_activity_completed($cmid)) {
                        $this->status = self::STATUS_COMPLETE;
                    } else {
                        $this->status = self::STATUS_INCOMPLETE;
                        break;
                    }
                }
            }
        }

        return $this->status;
    }

    /**
     * Check if the provided activity should be in a list of all activities for the section.
     *
     * @param \cm_info $activity Activity.
     *
     * @return bool
     */
    protected function is_activity_valid($activity) {

        $config = get_config('format_simple_topics');
        if ($config->displayhiddentopics == "1" && $activity->url instanceof \moodle_url) {
            if ($activity->visible == 0) {
                if (has_capability('moodle/course:viewhiddensections', \context_system::instance())) {
                    return true;
                }
                return false;
            }
            return true;
        }

        if ($activity->uservisible && $activity->url instanceof \moodle_url) {
            return true;
        }

        return false;
    }

    /**
     * Check if provided section activity is completed.
     *
     * @param int $cmid Activity.
     *
     * @return bool
     */
    public function is_activity_completed($cmid) {
        if (empty($this->get_activities()[$cmid])) {
            return false;
        }

        return ($this->get_activity_completion($this->get_activities()[$cmid])->completionstate == COMPLETION_COMPLETE ||
            $this->get_activity_completion($this->get_activities()[$cmid])->completionstate == COMPLETION_COMPLETE_PASS);
    }

    /**
     * Get activity completion data.
     *
     * @param \cm_info $activity Activity.
     *
     * @return object
     */
    protected function get_activity_completion($activity) {
        return $this->get_course_completion()->get_data($activity, false);
    }

}

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
 * Renderer
 *
 * @package    format
 * @subpackage simple_topics
 * @author     Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_simple_topics\output;

use format_simple_topics_content_footer;
use html_writer;

class renderer extends \format_topics\output\renderer {

    /**
     * Generate a summary of a section for display on the 'course index page'
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    protected function section_summary($section, $course, $mods) {
        $progress = new \format_simple_topics\section_progress($section);

        if (empty($progress->get_activities())) {
            return '';
        }

        $classattr = 'section main section-summary clearfix';
        $linkclasses = '';

        // If section is hidden then display grey section link.
        if (!$section->visible) {
            $classattr .= ' hidden';
            $linkclasses .= ' dimmed_text';
        } else if (course_get_format($course)->is_section_current($section)) {
            $classattr .= ' current';
        }

        if ($progress->is_completed()) {
            $classattr .= ' completed';
        } else {
            $classattr .= ' incompleted';
        }

        $titletext = get_section_name($course, $section);
        $title = $this->output->heading(html_writer::span($titletext), 3, 'section-title');

        $number = html_writer::tag('div', $section->section, array('class' => 'section-number'));
        $tick = html_writer::tag('div', '', array('class' => 'section-tick'));

        $o = '';

        $config = get_config('format_simple_topics');
        if (!$section->uservisible && $config->displayhiddentopics == "1") {
            $classattr .= ' locked_topic';
            $classattr .= ' dimmed_text';
            $classattr .= ' hidden';
        }

        $o .= html_writer::start_tag('li', array('id' => 'section-' . $section->section,
            'class' => $classattr, 'role' => 'region', 'aria-label' => $titletext));

        $o .= html_writer::tag('div', '', array('class' => 'left side'));
        $o .= html_writer::tag('div', '', array('class' => 'right side'));
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        if ($section->uservisible || $config->displayhiddentopics == "1") {
            $o .= html_writer::tag('a', $title . $number . $tick,
                array('href' => course_get_url($course, $section->section), 'class' => $linkclasses));
        }

        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');

        return $o;
    }

    /**
     * Render course footer.
     *
     * @param \format_simple_topics_content_footer $content
     *
     * @return string
     */
    protected function render_format_simple_topics_content_footer(format_simple_topics_content_footer $content) {
        $html = '';
        $linksrenderer = $this->page->get_renderer('format_simple_topics', 'navigation_links_activity');

        $html .= \local_activity_progress\html_helper::emoticon();
        $html .= $linksrenderer->render_navigation_links();

        return $html;
    }
}

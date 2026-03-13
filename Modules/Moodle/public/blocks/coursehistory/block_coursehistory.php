<?php

require_once($CFG->dirroot . '/local/coursehistory/lib.php');

class block_coursehistory extends block_base
{

    public function init()
    {
        $this->title = get_string('pluginname', 'block_coursehistory');
    }

    public function applicable_formats()
    {
        return [
            'admin' => false,
            'site-index' => true,
            'course-view' => false,
            'mod' => false,
            'my' => true,
        ];
    }

    public function instance_allow_multiple()
    {
        return false;
    }

    public function get_content()
    {

        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $profileurl = new moodle_url('/local/coursehistory/profile.php', [
            'userid' => $USER->id
        ]);

        $submissions = local_coursehistory_get_user_submissions($USER->id);

        $html = '';

        if (!empty($submissions)) {

            $html .= html_writer::start_tag('ul', ['class' => 'list-unstyled']);

            $count = 0;

            foreach ($submissions as $sub) {

                if ($count >= 5) {
                    break;
                }

                $viewurl = new moodle_url(
                    '/local/coursehistory/view.php',
                    ['id' => $sub->id]
                );

                $status = local_coursehistory_status_badge($sub->status);

                $html .= html_writer::start_tag('li', [
                    'style' => 'margin-bottom:8px;'
                ]);

                $html .= html_writer::link(
                    $viewurl,
                    format_string($sub->coursename)
                );

                $html .= html_writer::tag(
                    'div',
                    '<small>' . $status . '</small>'
                );

                $html .= html_writer::end_tag('li');

                $count++;
            }

            $html .= html_writer::end_tag('ul');
        } else {

            $html .= html_writer::tag(
                'div',
                '<small>' . get_string('nosubmissions', 'block_coursehistory') . '</small>'
            );
        }

        $this->content->text = $html;

        $this->content->footer = html_writer::link(
            $profileurl,
            get_string('viewall', 'block_coursehistory')
        );

        return $this->content;
    }
}

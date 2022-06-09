Workflow report
https://moodle.org/plugins/report_workflow

This report goes with the workflow block https://moodle.org/plugins/block_workflow.
It was created for the Open University https://www.open.ac.uk/) by LUNS
(http://www.luns.net.uk/services/virtual-learning-environments/vle-services/).
The specification was written by Tim Hunt and Sharon Monie.

You can install this report from the Moodle plugins database using the link above.

Alternatively, you can install it using git. Type this command in the top level
of your Moodle install:

    git clone https://github.com/moodleou/moodle-block_workflow.git blocks/workflow
    echo '/blocks/workflow/' >> .git/info/exclude
    git clone https://github.com/moodleou/moodle-report_workflow.git report/workflow
    echo '/report/workflow/' >> .git/info/exclude

Once you have added the code to Moodle, visit the admin notifications page to
complete the installation.

For more documentation, see http://docs.moodle.org/en/The_OU_workflow_system.

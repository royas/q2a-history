====================================
Question2Answer History v 1.0b
====================================
-----------
Description
-----------
This is a plugin for **Question2Answer** that adds an activity/points history to the user profile.

--------
Features
--------
- shows almost all events in list
- shows points gained and lost for each event if applicable.
- option to replace old activity list 
- set max age of events to show in admin/plugins
- css configurable, all strings configurable via admin/plugins
- incorporates User Activity Plus plugin by Scott Vivian

------------
Installation
------------
#. Install Question2Answer_
#. Get the source code for this plugin from github_, either using git_, or downloading directly:

   - To download using git, install git and then type 
     ``git clone git://github.com/NoahY/q2a-history.git history``
     at the command prompt (on Linux, Windows is a bit different)
   - To download directly, go to the `project page`_ and click **Download**

#. extract the files to a subfolder such as ``history`` inside the ``qa-plugins`` folder of your Q2A installation.
#. navigate to your site, go to **Admin -> Plugins** on your q2a install and select options, then click **Save Changes**.

.. _Question2Answer: http://www.question2answer.org/install.php
.. _git: http://git-scm.com/
.. _github:
.. _project page: https://github.com/NoahY/q2a-history

----------
Disclaimer
----------
This is **beta** code.  It is probably okay for production environments, but may not work exactly as expected.  Refunds will not be given.  If it breaks, you get to keep both parts.

-------
Release
-------
All code herein is Copylefted_.

.. _Copylefted: http://en.wikipedia.org/wiki/Copyleft

---------
About q2A
---------
Question2Answer is a free and open source platform for Q&A sites. For more information, visit:

http://www.question2answer.org/


From User Activity Plus plugin:


-------------------------------------------------
USER ACTIVITY PLUS
Question2Answer plugin
-------------------------------------------------

This is a page plugin for popular open source Q&A platform, Question2Answer <question2answer.org>. It adds the functionality to show every question and answer of a user.

The posts list is paginated and uses the value set under Admin > Lists > Questions page length (default 20). Answers use a similar design to question lists but also shows a snippet of the answer. Both lists include the class `qa-a-count-selected` where applicable for appropriate styling of selected answers.



INSTALLATION & USAGE
-------------------------------------------------

1. Download and extract the files to a subfolder such as `user-activity-plus` inside the `qa-plugins` folder of your Q2A installation.

2. Copy the styles from sample.css to your theme's stylesheet.

3. Now on a user's profile are links to see all questions and answers of the user. They are show both in the activity stats table and below the "Recent Activity" question list.


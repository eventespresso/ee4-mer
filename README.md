#EE4 MER : Multi Event Registration Add-on for Event Espresso 4

[![GitHub release](https://img.shields.io/badge/Release%20Candidate-v2.0.4.rc-orange.svg?style=plastic)](https://github.com/eventespresso/event-espresso-core)
[![WordPress](https://img.shields.io/badge/WordPress-v4.2%20tested-brightgreen.svg?style=plastic)](http://eventespresso.com/)
[![License](https://img.shields.io/badge/License-GPLv2-blue.svg?style=plastic)](https://www.gnu.org/licenses/gpl-2.0.html)
[![License](https://img.shields.io/badge/Eco%20Certified-Dolphin%20Friendly-ff69b4.svg?style=plastic)](http://eventespresso.com/)

## Lots of events to register for?

**Now you can add events to a registration Event Cart (shopping cart), then register for them all at once.**

![mutliple_380_250_addon.jpg](http://eventespresso.com/wp-content/uploads/2011/10/mutliple_380_250_addon.jpg "Multi Event Registration addon for Event Espresso 4")

The premise behind this great add-on is that the easier it is for people to register for an event, the more events they are likely to attend. With the Multiple Event Registration add-on, attendees can register for several events at once by adding them to the Event Cart (shopping cart), then proceeding to the registration checkout when they are ready.

This plugin/add-on needs to be uploaded to the "/wp-content/plugins/" directory on your server or installed using the WordPress plugins installer. Once the plugin/add-on is installed and activated visit your events listing page to see it in action. Please note that there is also a mini event cart widget that can be found on the WordPress Widgets admin page.

> This README.md file is targeted for display with our Github repo.

> Extra:  The code structure and phpdoc parsed documentation can be found at http://code.eventespresso.com

> Developer Targeted Documentation can be found at http://developer.eventespresso.com

## Event Espresso Releases

At Event Espresso we follow a set pattern for releases:

1. Active development for new features happens on a **FET-{ticket-number}** branch.  We continually merge master into the feature branch while its in development.  Once its complete, then testing is done on it and its merged back to master ready for release.

2. Bug fixes etc. are done on a **BUG-{ticket-number}** branch.  Same methodology is used as with Feature branches.

3. Stable releases are tagged both with a tests folder and without the tests folder.

4. Master is technically always production ready and release ready but may not be equal to what the current stable release is (that is what tags are for).


## Testing
For all testers on github, please take note of the following when reporting issues.

1. **There is a difference between a feature and a bug** We consider a bug is something that reveals brokenness in intended functionality.  A feature, is something beyond intended functionality.  To help determine the difference, think about your issue like this, "I know A does C, however I *wish* it did D."  If you find yourself saying that, its a feature.  For Event Espresso,  Github is not the place to suggest a new feature UNLESS you've already got a pull request to implement it (see pull requests section below).  Info on sponsoring new features can [be found here](http://eventespresso.com/rich-features/sponsor-new-features/).  If you aren't sure whether something is a feature or bug feel free to post the issue - however we give priority to bug issues here.

2. **UI/UX issues may be considered a bug but not if it requires a major change in design.**  Feel free to report things you find confusing or needing improvement however reports accompanied by a pull request will likely get faster attention.

3. **Report your issue as clearly as possible.**  By "clear" we mean:

	i. Specify the branch this occurred in.

	ii. Be specific about the steps you took to reproduce.

	iii. Feel free to use screenshots/screencasts to illustrate

	iv. Use URLs for the page the issue to place on where possible.

4. **Don't "bump" bug reports if we don't respond right away.**  We see every report coming in, but we'll only reply if we need clarification or if we think its invalid.  Otherwise, we're likely working on a fix and the issue will be updated when the fix is complete.

## Pull Requests
One of the reasons we created this private repo on github is because we wanted to open up EE development to 3rd party developers who might want to contribute to the codebase. Github makes this really easy to do so via pull requests.  If you don't know what pull requests are, please read up on them via the github help/documentation.

**Here's how we deal with pull requests for our repo:**

1. Any new FEATURES in a pull request should be based off of the *master* branch. If your feature pull request is based off any other branch it will not be considered.

2. Any BUGFIX pull requests should be based off of the branch the bug was found.  Please verify if it is in master before submitting the pull request.  If it is in reproducible on master, we'd prefer to have the pull request based off master.

3. We greatly appreciate any pull-requests submitted for consideration, but please understand we are very selective in what we decide to include in EE core.  If the "feature" is something that expands too much on our design decisions for EE core then we may suggest you develop your pull request into an addon for EE.









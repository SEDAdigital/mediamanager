# Media Manager for MODX [BETA]
The Media Manager is a MODX Extra replacing the default Media Browser with an enterprise-grade media management solution, an initiative by [SEDA](https://seda.digital/) and [Sterc](https://www.sterc.com).

## Features
- Fully responsive
- Built on jQuery and Bootstrap for easier future development
- Fully database driven
- Virtual categories, instead of folders to allow a file to be in multiple categories
- Tag support
- Version control for your media
- Image editing features, including resizing and cropping
- Ability to publish/unpublish media with reference-checking
- Support for TinyMCE, TV's, ContentBlocks
- Meta data for images out of the box: id, version, filename, version, author, dimensions (original + cropped image size), category, tags)
- Unlimited additional meta data (like TV's for images!)
- MODX ACL for removing/archiving images

## Migrating from the default media browser
A migration tool is not yet available. You will not loose your old images, but they also will not show up in the new media browser.

## Installation
1. Install the package through package management by uploading the latest version from the _packages folder.
2. You have to enable the Media Manager per MediaSource. Go to the top-menu "Media" -> "Media Sources" -> "Create property": Name=mediamanagerSource, Type=Textfield, Value=1
3. Now enable it per user by editing a user: "Manage" -> "Users" -> Edit a user -> "Settings" -> "Create new" and set the key and/or name to "media_sources_id" and set the value to the ID of the Media Source you just enabled.

That's it, have fun!

## BETA BETA
Please note that this manager is BETA. Please create your issues in Github or create Pull Requests. Any questions? Email us at modx@sterc.nl

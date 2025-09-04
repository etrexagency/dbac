# ETX: Date-based Access Controller

## Description

This add-on allows you to restrict access to media pool files, articles (pages), and modules based on a date specified for publication and deactivation. Access to media pool files and articles (pages) can be controlled via meta information. For modules, this is not possible via meta information. For this purpose, code is provided that can be added to the input and output of the developer add-on.

### Restriction of media pool files

If you select Media Pool in the add-on settings, the fields `med_publication_date` and `med_deactivation_date` are created in the add-on **Meta info** under the category **Media**. Using the extension point `MEDIA_MANAGER_BEFORE_SEND`, the system checks whether you are authorized to access the file when you call it up. If not, the file cannot be accessed in the frontend and a 404 error is thrown.

### Restriction of articles (pages)

If you select **Articles (pages)** in the add-on settings, the meta information fields `art_publication_date` and `art_deactivation_date` are created under the **Articles** category. When the file is called up, the extension point `ART_INIT` checks whether the article can be accessed or not. If not, the page cannot be accessed by logged-out users and a 404 error is thrown.

### Restriction of modules

The settings contain sample code for the input and output (Developer AddOn) to copy in order to implement access restrictions for modules.

## Requirements

Developed on:

- Redaxo version: 5.17.0

Access restriction at media pool and article (page) level:

- mediapool (Redaxo Standard) >= 2.10.0 for the media pool
- metainfo (Redaxo Standard) >= 2.10.0 for entering the fields for “publication date” and “deactivation date”
- [flatpickr](https://github.com/FriendsOfREDAXO/flatpickr) >= 8.0.4 for visual date selection when entering the date

Access restriction at module level (code template):

- [developer](https://github.com/FriendsOfREDAXO/developer) >= 3.9.0 for module input and output
- [MForm](https://github.com/FriendsOfREDAXO/mform) >= 8.0.0 for module input fields

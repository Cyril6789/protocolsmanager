# Protocols Manager

Protocols Manager is a GLPI plugin for creating, storing, and managing PDF documents related to equipment in GLPI. It provides two document workflows:

- user inventory reports, which list equipment assigned to a user;
- asset commissioning certificates (*procès-verbaux de mise en service*), which record the commissioning of one or more assets.

The generated PDFs are stored as GLPI Documents and linked to the relevant GLPI records. The plugin is maintained at [github.com/Cyril6789/protocolsmanager](https://github.com/Cyril6789/protocolsmanager). The plugin card in GLPI also includes a shortcut to this project.

![Protocols Manager setup](docs/img/setup.gif)

## Features

### User inventory reports

- Create a PDF from all or a chosen subset of equipment assigned to a user.
- Open the inventory from a user record, or from a supported equipment record to see its assigned user's inventory.
- Review equipment type, manufacturer, model, name, serial number, inventory number, and comments.
- Choose how serial and inventory numbers appear: in separate columns, or as one value using the serial number when available and the inventory number otherwise.
- Edit a comment for each selected item, add custom rows, and add a note to the report.
- Choose a document template before generating the PDF.
- View generated reports in the plugin's document list and in GLPI's Documents, open them, delete selected reports, or send them by email.

![Inventory report configuration](docs/img/config.gif)

![Generating an inventory report](docs/img/generate_standard.gif)

### Asset commissioning certificates

The **PV de mise en service** tab is available on computers, monitors, printers, peripherals, phones, network equipment, and software licenses.

The certificate includes:

- an identification table with the asset designation, inventory number, serial number, supplier, purchase date, invoice number, acquisition value before tax, and the top-level location;
- the GLPI asset number (*numéro immo*), when available;
- a commissioning statement signed with the current user's first and last name, four preselected but editable status checkboxes (installed, tested, accepted as compliant, and put into operation), the effective commissioning date, and a planned-use description;
- an observations section, which defaults to “RAS” when left empty;
- a validation table for the user manager, technical manager (if applicable), accounting, and management/line manager, with space for name, signature, and date;
- the document date and a choice between Eichhoffen and Barr as the place of issue.

The PDF uses the logo and highlight color selected in the plugin's document template settings. Chapter 4 starts on the second page. Empty GLPI management fields remain blank in the certificate; they do not prevent generation.

Assets with the same asset number can be included in one certificate, subject to the user's GLPI read access to those assets. The PDF includes the total acquisition value for a multi-asset certificate when the values are available. The generated document is linked to each selected asset. When the source asset has no asset number, a certificate can still be generated for that equipment; its asset number is left blank and the document is linked to the source equipment. A certificate can also be generated with no management information: the identification table remains present with blank values.

A certificate can also be started from a software license. Its asset number is read from the license's GLPI management information and appears in the PDF and its filename when present. The PDF uses the license's available name, type, serial number, and inventory number, and is linked to that license. License certificates are generated separately from the hardware groups; acquisition and location fields stay blank when GLPI has no values for them.

![Commissioning certificate workflow](docs/img/generate_com.gif)

## Requirements and compatibility

- GLPI 11.x or GLPI 12.x (the plugin declares compatibility with GLPI versions from 11.0 up to, but not including, 13.0).
- PHP 8.0 or newer for the plugin. GLPI 12 requires PHP 8.3 or newer.
- The GLPI Documents feature and the user's GLPI rights must allow document creation to generate PDFs.

## Installation

1. Download or clone this repository.
2. Copy the `protocolsmanager` directory into the GLPI `plugins` directory.
3. In GLPI, open **Setup → Plugins**, install Protocols Manager, then activate it.
4. Grant the required plugin and document rights to the appropriate profiles (see [Permissions](#permissions)).

## Updating

1. Back up the GLPI database and plugin directory according to your normal GLPI maintenance procedure.
2. Replace the existing `protocolsmanager` directory with the new release files, preserving any local changes you intentionally maintain.
3. In **Setup → Plugins**, run the available plugin update and confirm that the plugin is active.

## Configuration and templates

Open **Plugins → Protocols Manager** to manage document templates. Create or edit a template and save it before selecting it during PDF generation.

Templates can configure:

- template name and document title;
- font and font size, portrait or landscape orientation, and word breaking;
- primary PDF color and heading/text color;
- logo image (PNG or JPEG) and its width and height;
- city, upper content, main content, and footer;
- inventory report serial/inventory number display mode;
- PDF author, either the current PDF generator or a fixed author name;
- optional automatic email sending and the email template to use.

Text fields for titles, content, and email templates support these substitutions:

| Token | Replaced with |
| --- | --- |
| `{owner}` | The inventory owner’s name |
| `{admin}` | The current PDF generator’s name |
| `{cur_date}` | The current date, formatted as `d.m.Y` |

Email templates define a name, recipients, subject, and message. They can be configured to include the inventory owner. Reports can also be sent manually from the generated-document list. Email delivery depends on the GLPI mail configuration.

The template's logo and colors are also used by commissioning certificates. The commissioning form supplies its certificate-specific fields; inventory report content settings do not replace those fields.

![Template configuration](docs/img/config.gif)

## Permissions

Configure access under **Administration → Profiles → [profile] → Protocols Manager**:

- **Plugin configuration** grants access to the plugin's configuration and template management. GLPI configuration-update rights are also required to save configuration.
- **Tab access** grants access to the plugin tabs on supported GLPI records.
- **Document creation** is a GLPI Documents right required to generate PDFs. Without it, users may view existing commissioning certificates when they have access to the tab, but cannot generate new ones.

Users also need the ordinary GLPI rights to read the relevant users and assets. For grouped commissioning certificates, the matching assets must be readable for them to be included.

## Using the plugin

### Generate a user inventory report

1. Open a user record (or a supported equipment record assigned to that user) and select the **Protocols Manager** tab.
2. Select all or some listed items. Edit item comments if needed.
3. Add custom rows or an export note if needed.
4. Choose a template and generate the PDF.
5. Open the generated document from the list. It is also available under **Management → Documents** and is linked to the user and the included equipment.
6. Select documents in the list to delete them or send them by email when those actions are available to your profile.

### Generate a commissioning certificate

1. Open a computer, monitor, printer, peripheral, phone, network equipment, or software license record and select **PV de mise en service**.
2. If the asset has an asset number, review the assets with the same number and select which ones belong in this certificate. If it has no asset number, the certificate can still be made for the current equipment.
3. Choose the template whose logo and colors should be used.
4. Fill in the commissioning date, planned use, observations, status checkboxes, and place of issue as needed. The date defaults to the acquisition date when it is available and consistent for the selected assets.
5. Generate the PDF. It appears in the tab's generated-document list and in GLPI Documents, linked to the selected asset or assets (or to the current license).

## Where generated documents are stored

Protocols Manager creates standard GLPI Documents. They can be opened from the plugin's document list and from **Management → Documents**. Inventory reports are associated with their user and included equipment. Commissioning certificates are associated with each selected asset; certificates made without an asset number are associated with the source equipment.

## Project and support

- Source code, releases, and issue tracking: [github.com/Cyril6789/protocolsmanager](https://github.com/Cyril6789/protocolsmanager)
- The project is based on the original Protocols Manager plugin by Mateusz Nitka. Thanks to [Nomino](https://nomino.pl/) for supporting the original project.

![Nomino](docs/img/logo-nomino.png)

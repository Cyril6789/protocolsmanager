# Protocols Manager

Protocols Manager is a GLPI plugin for creating and managing PDF documents from inventory data. Version **2.0.0** provides three document workflows:

- **Inventory reports** for equipment assigned to a user.
- **Commissioning certificates** (*procès-verbaux de mise en service*).
- **Disposal certificates** (*procès-verbaux de mise au rebut*).

Generated PDFs are stored as GLPI Documents and linked to the relevant GLPI records. Inventory reports have an unlimited, independently managed set of templates. Commissioning and disposal each use their own dedicated, configurable form and PDF model. A single global logo and accent color are shared by all three document types.

## Features

### Inventory reports

- Generate a PDF for all or selected equipment assigned to a user. The inventory tab is available on user records and supported equipment records.
- Inspect type, manufacturer, model, name, serial number, inventory number, GLPI status, assigned person, and purchase price in the selection table. The status, assigned person, and purchase price are selection aids and are not added to the PDF by these columns.
- Choose whether serial and inventory numbers use separate columns or a single column that falls back to the inventory number when no serial number exists.
- Add a comment to each selected item, add custom rows, and enter a report note.
- Create as many inventory templates as needed. Each template can set its document title, font, font size, page orientation, word breaking, city, upper text, main text, footer text, number display mode, PDF author, and automatic email behavior.
- View generated reports in the plugin list and GLPI Documents, open or delete reports, and send them by email when available to the profile.

### Commissioning certificates

The **Procès-Verbaux** tab is available on computers, monitors, printers, peripherals, phones, network equipment, and software licenses.

- Select which assets sharing the same asset number (*numéro immo*) to include. The selection table shows the name, type, model, inventory number, status, assigned person, purchase price, and serial number. A select-all control selects or clears the entire list.
- Generate a certificate without an asset number when the current hardware or license has no GLPI management information. The PDF leaves the asset number blank and the document remains linked to the current record.
- Configure the form field labels and visibility, commissioning status checkboxes, validator roles, and available places. Checkbox labels, visibility, and default checked state are editable; validator roles and place choices can be added, renamed, activated, or removed.
- Configure the title, visibility, and introductory text of each standard PDF chapter. Add or remove free-form annexes. Standard chapters have a fixed order; their generated asset data and tables are supplied by the plugin.
- The effective commissioning date uses the unique purchase/acquisition date available in GLPI for the selected group as its default. When available, the form states this fallback beside the date field. If no unambiguous date is available, the field is left blank for entry.
- The generated document contains asset identification, commissioning statements and selected checkboxes, planned use, observations, a validator signature table, and the selected place when those chapters and fields are enabled. If observations are empty, the PDF uses `RAS`.
- The `{observations}` marker in the observations chapter introduction is replaced with the entered observation and is not printed twice.
- The document is linked to every selected asset. Certificates started from a software license remain linked to that license and are handled separately from hardware groups.

### Disposal certificates

- Select which assets sharing the same asset number to include. The selection table displays the same identifying and management information as the commissioning selector and has a select-all control.
- Generate a certificate without an asset number for the current hardware or license record.
- Configure the title, form fields and labels, disposal methods, checklist items, standard chapter titles/visibility/introductory text, and free-form PDF annexes.
- Add, rename, enable, disable, or remove disposal methods and checklist items. Configure the default checked state for each checklist item. If **Autre** is available and selected, the user must provide its details.
- The generated PDF records the disposal date, requester and job title from the current GLPI user, asset identification, reason, selected method, checklist state, and signature section when enabled by the model.
- `{reason}` and `{method}` in their corresponding chapter introductions are replaced with the submitted values; values are not repeated beneath the introduction when its marker is used.
- The detailed asset information remains in the PDF. The redundant **Détails de l’immobilisation** table has been removed from the form; the asset selection table remains.

### Shared PDF branding and footer

The **Identité visuelle** settings provide one PNG/JPEG logo and one accent color for inventory reports and both certificate types. The logo must be no larger than 2 MB or 5,000 × 5,000 pixels. Removing or replacing it updates branding for all generated PDFs.

Every page has a shared footer with the logo at the left, document type centered, asset number centered on the next line when available, and page count at the right aligned with that second line. The inventory template's configured footer text is retained in the inventory PDF footer.

### Email templates

- Create, edit, and delete reusable email templates with recipients, subject, message, and an option to include the inventory owner as a recipient.
- Inventory templates can enable automatic email delivery after generation. Reports can also be sent manually from the document list.
- Email delivery uses GLPI's configured mail transport. Template text supports the inventory substitutions listed below.

## Configuration

Open **Plugins → Protocols Manager**. The configuration screen has separate sections for **Inventory templates**, **PV de mise en service**, **PV de mise au rebut**, **Global branding**, and **Emails**.

Inventory template text and email subject/message fields support these substitutions:

| Token | Value |
| --- | --- |
| `{owner}` | Inventory owner's name |
| `{admin}` | Name of the user generating the report |
| `{cur_date}` | Current date, formatted as `d.m.Y` |

Each PV has one dedicated model; users do not pick a different PV model while generating a certificate. For standard chapters, the configuration screen controls title, visibility, and introductory text. Dynamic tables and asset fields are populated by the plugin from GLPI and the form. Additional free-form chapters are rendered as numbered annexes after the standard chapters. The order of standard chapters and their structured fields are fixed.

The global branding setting applies to all PDFs. Existing inventory template typography, page orientation, content, and footer remain configured on each inventory template.

## Permissions

Configure the plugin rights under **Administration → Profiles → [profile] → Protocols Manager**:

- **Plugin configuration** grants access to the configuration page. Saving settings also requires GLPI configuration update rights.
- **Tab access** grants access to Protocols Manager tabs on supported GLPI records.
- **Document creation** is the GLPI Documents right required to generate PDFs.

Users also need the ordinary GLPI read rights for the users and assets involved. Grouped PV generation validates access to each selected asset. The availability of send/delete actions depends on the user's rights and the GLPI mail setup.

## Usage

### Generate an inventory report

1. Open a user record or a supported equipment record associated with a user, then open the **Protocols Manager** tab.
2. Select the inventory template and the items to include. The item table includes status, assigned person, and purchase price for reference.
3. Add per-item comments, custom rows, or a report note as needed.
4. Generate the PDF. It is available from the plugin's document list and **Management → Documents**, and is linked to the user and included equipment.

### Generate a commissioning certificate

1. Open a supported hardware or software license record and select **Procès-Verbaux**.
2. Expand **PV de mise en service**. Select the assets to include when they share an asset number, then complete the enabled fields and checkboxes.
3. Review the effective date. The form uses a unique GLPI purchase date as the initial value when available; otherwise enter a date if required for the certificate.
4. Generate the PDF. It appears in the PV document list and GLPI Documents, linked to the selected assets or the current record.

### Generate a disposal certificate

1. Open a supported hardware or software license record and select **Procès-Verbaux**.
2. Expand **PV de mise au rebut**. Select the assets to include when they share an asset number.
3. Complete the enabled fields, enter the reason, choose a configured disposal method, provide details for **Autre** when applicable, and set the checklist state.
4. Generate the PDF. It is stored in GLPI Documents and linked to the selected assets or the current record.

## Requirements and compatibility

- GLPI **11.0 or newer and below 13.0** (the plugin declares GLPI 11.x and 12.x compatibility).
- PHP **8.0 or newer** for the plugin. GLPI 12 itself requires PHP 8.3 or newer.
- GLPI Documents must be available, and the user's profile must have the required document rights to generate PDFs.

## Installation and updates

1. Back up the GLPI database and plugin files before installing or updating.
2. Place the `protocolsmanager` directory in GLPI's `plugins` directory.
3. Open **Setup → Plugins**, install Protocols Manager, then activate it.
4. Assign the plugin and document rights to the appropriate profiles.

For an existing installation, deploy the new plugin files and use GLPI's plugin update workflow if GLPI offers one. Keep the existing database and do not uninstall the plugin as part of an update: the uninstall hook removes the plugin's configuration and document-index tables. The plugin installation hook creates any missing settings table and initializes the PV defaults while retaining existing inventory templates and email settings.

## Project

- Source, releases, and issue tracking: [github.com/Cyril6789/protocolsmanager](https://github.com/Cyril6789/protocolsmanager)
- The project builds on the original Protocols Manager plugin by Mateusz Nitka. Thanks to [Nomino](https://nomino.pl/) for supporting the original project.

![Nomino](docs/img/logo-nomino.png)

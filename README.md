# Islandora IIIF hOCR

## Introduction

This module is part of the Islandora project and extends the Islandora IIIF component module
to support IIIF search results annotations. This provides the back-end support for
on-page highlighting of search results within image viewers such as [Mirador](https://github.com/ProjectMirador/mirador) 
via the [Islandora Mirador](https://github.com/islandora/islandora_mirador) module.
On its own this module does not provide any end-user functionality.

## Installation 

Until this gets added to Packagist, you will need to add this project to your
composer.json's repositories section.

### Configuring Search Highlighting annotations

To prepare to perform text searches with result coordinate highlights, islandora_mirador requires several additional configuration steps after those previously made to enable Mirador with text overlay:
- The [solr-ocrhighlighting](https://github.com/dbmdz/solr-ocrhighlighting) plugin needs to be installed in your islandora installation's solr server in `/opt/solr/server/solr/contrib/ocrhighlighting/lib`. These instructions have been tested with version 0.7.2. This might be done using the following commands (assuming a typical docker setup with a "solr" container):
```
	curl -k -L https://github.com/dbmdz/solr-ocrhighlighting/releases/download/0.7.2/solr-ocrhighlighting-0.7.2.jar > data/solr-ocrhighlighting.jar
	docker-compose exec -T solr with-contenv bash -lc "mkdir -p /opt/solr/server/solr/contrib/ocrhighlighting/lib"
	docker cp data/solr-ocrhighlighting.jar $$(docker-compose ps -q solr):/opt/solr/server/solr/contrib/ocrhighlighting/lib/solr-ocrhighlighting.jar
	docker-compose exec -T solr with-contenv bash -lc "chown -R solr:solr /opt/solr/server/solr/contrib/ocrhighlighting"
	docker-compose restart solr

```
- The schema.xml and solrconfig.xml files, located at `/opt/solr/server/solr/ISLANDORA/conf/` in your solr container, need to be edited. Samples can be found in `docs/solr-ocr-setup` distributed with this module):
  - The solrconfig.xml needs to be edited to load the `solr-ocrhighlighting-0.7.2.jar` file, and to define a "ocrHighlight" search component that uses it.
  - The schema.xml needs to be edited to add a solr field named "ocr_text".
- Search api solr field types be added which define "ocr_highlight" field types for each language that you support in your Islandora installation. Typically, english (`en`) and "undefined" (`und`) field types would be needed. Configuration files for these can be found in `docs/solr-ocr-setup` distributed with this module.
- Add another field to the **File** media type, this one to hold the editable hOCR text that is generated. This field will hold the exact same text as that contained by the file that is attached to the extracted hOCR text file field. This field should be of the type "Text (formatted, long)". This could be called "Editable hOCR text".
- Edit the "hOCR for Media Attachment" action that you created earlier, and add the editable hocr text field as a *Destination Text Field Name*<br />
![add-text-field-destination-to-hocr-generate-action.png](docs/add-text-field-destination-to-hocr-generate-action.png)
- In your solr index you will need to enable media entity indexing, and then add a field that indexes the editable hocr text field on the file media type (`field_editable_hocr_text`)
  ![solr-media-file-field_editable_hocr_text.png](docs/solr-media-file-field_editable_hocr_text.png)

- Finally, on the Islandora Miradora configuration form...
![islandora_mirador-config-form-ocr-highlighting.png](docs/islandora_mirador-config-form-ocr-highlighting.png)
  - Select the views and displays that are used to generate your IIIF Manifests for "Paged Content" and "Page" objects, respectively.
  - Select the solr field in which you are indexing the hocr editable text field on media entities. To do this, you must first select which solr index this field is found in (normally there is just one).

## Documentation

Further documentation for IIIF (International Image Interoperability Framework) is available on the [Islandora 8 documentation site](https://islandora.github.io/documentation/user-documentation/iiif/).

## Troubleshooting/Issues

Having problems? Solved a problem? Join the Islandora [communication channels](https://www.islandora.ca/community#channels-of-communication) to post questions and share solutions:

* [Islandora Mailing List (Google Group)](https://groups.google.com/g/islandora)


* If you would like to contribute or have questions, please get involved by attending our weekly [Tech Call](https://github.com/Islandora/islandora-community/wiki/Weekly-Open-Tech-Call), held virtually via Zoom **every Wednesday** at [**1:00pm Eastern Time US**](https://dateful.com/convert/est-edt-eastern-time?t=13). Anyone is welcome to join and ask questions! The Zoom link can be found in the meeting minutes [here](https://github.com/Islandora/islandora-community/wiki/Weekly-Open-Tech-Call).

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](https://github.com/Islandora/islandora-community/wiki/Onboarding-Checklist#contributor-license-agreements) or [Corporate Contributor License Agreement](https://github.com/Islandora/islandora-community/wiki/Onboarding-Checklist#contributor-license-agreements). Please see the [Contributor License Agreements](https://github.com/Islandora/islandora-community/wiki/Contributor-License-Agreements) page on the islandora-community wiki for more information.

## Acknowledgements
- The IIIF Search API code used in this module, and the solr ocr highlighting configuration that it depends on, is built off of, and gratefully indebted to, the work of [Diego Pino](https://github.com/DiegoPino), [Giancarlo Birello](https://github.com/giancarlobi) and other contributors to the Archipelago Commons open source initiative of the [Metropolitan New York Library Council](https://metro.org/).

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)

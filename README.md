# Mage2 Module MageGuide MergeSvgs M2
tested on 2.2.6

Merges all svgs in app design into single svg image per folder and edits less files to include the merged image (per directory).
Reduces the number of total requests made to fetch images. Faster Page Load in HTTP 1 setups works well with varnish and chrome/safari

\* Firefox not supported no gain in performance

## Main Functionalities
 - Merges Svgs Images
 - Alters .less files to include Merged images


## Installation
Place in app/code/MageGuide/MergeSvgs
\* Required MageGuide_MGbase


### Type 1: Zip file

 - Unzip the zip file in `app/code/MageGuide`
 - Enable the module by running `php bin/magento module:enable MageGuide_MergeSvgs`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Screenshots

 

## Customization




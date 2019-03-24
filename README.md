# flatnuke2grav

This utility helps you to convert some Flatnuke CMS contents to Grav:
- Flatnuke homepage: http://flatnuke.netsons.org/
- Grav homepage: https://getgrav.org/

## Usage

1. Configure the script filling your specific information if needed:
```php
// basic URL of your Flatnuke installation (do NOT specify 'http/https' nor 'www')
$flatnuke_baseurl = "my.website.ext";
// Flatnuke section containing the news you want to move to Grav (none_News is the default choice)
$flatnuke_inpath  = "none_News";
// output path where Grav's new files will be created
$gravnews_outpath = "./sections/$flatnuke_inpath/none_newsdata_grav";
```
2. Copy the script into root installation directory of Flatnuke
3. Execute the script from your web browser
4. Move '$gravnews_outpath' content from Flatnuke path to '[*Grav_path*]/user/pages/XX.blog' directory
5. Remember to delete this script from your server once completed!

## License
GNU General Public License version 2 (https://opensource.org/licenses/GPL-2.0)

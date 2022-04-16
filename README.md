# ViewsCounter
<div>
  This wordpress plugin counts the views for all site pages and has a shortcode that show the total views of the site.
</div>
<br>
<div>
  When a client visits a page of the site, the plugin updates the respective table in wordpress MySQL database incrementing the number of views of that page or   creating a new row with the new page that had the first view.
</div>
<br>
<div>
  The plugin contains also a shortcode that show the amount of views of the entire site, which simply is the sum of views of each page
</div>
<br>
<div>
  If the user visits a page that had viewed before, in the same browser session, that visit will be ignored.
  The plugin also exclude the visits of the crawlers listed in /robots/_robotsList.php file.
</div>
<div>
  I also put a file names cUrl_useragent.php. If this script is executed, it do an HTTP request to update the robots list used by this plugin.
  I put an .htaccess file to prevent this command can be launched from every address, but you can modify this behavior in the way you want.
</div>
  

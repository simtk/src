# Custom logging

LogFormat "%h %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" fusionforge
CustomLog /var/log/gforge/access.log fusionforge
<IfModule mod_ssl.c>
LogFormat "%{Host}i %h %l %u %t %{SSL_PROTOCOL}x:%{SSL_CIPHER}x \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" combinedvhssl
</IfModule>
CustomLog /var/log/gforge/awstats.log combinedvhssl

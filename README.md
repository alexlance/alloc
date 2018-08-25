# allocPSA
allocPSA is the web-app that takes care of your projects, employees, time
sheets, invoicing and customers.

## Contact
Email [support@allocpsa.com](mailto:support@allocpsa.com) for commercial and
hosting enquiries.

## Developers
Feel free to submit a pull request if you're interested in improving alloc.

## How to install
allocPSA is generally intended to run with PHP>=5 and MySQL>=4 on a Linux
server.

1) Put the source code in a directory called e.g. `alloc` in your
httpd servers document root. E.g.: `/var/www/html/alloc/`

2) Make the patches and css, e.g.:

```
$ make patches; make css
```

3) In a web browser, go to your server's directory where you put the
alloc source code, eg: `http://localhost/alloc/`

4) Follow the instructions in the web browser to complete the installation.

## How to Upgrade
- Backup your allocPSA database. _DO IT NOW_.
- Unpack the new allocPSA source code alongside your current installation.
- Copy the `alloc_config.php` file from your current installation of
  allocPSA into the directory that contains the new installation of allocPSA.

- Finally, update your allocPSA database by going to this address in your web
  browser: `http://YOUR_NEW_ALLOC_INSTALLATION/installation/patch.php`

  Apply each patch separately, starting from the top and working your way
  down.

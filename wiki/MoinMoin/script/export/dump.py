# -*- coding: iso-8859-1 -*-
"""
MoinMoin - Dump a MoinMoin wiki to static pages

@copyright: 2002-2004 Juergen Hermann <jh@web.de>,
            2005-2006 MoinMoin:ThomasWaldmann,
            2013-2014 Paul Wise <pabs3@bonedaddy.net>
@license: GNU GPL, see COPYING for details.
"""

import sys, os, time, codecs, shutil, re, errno

from MoinMoin import config, wikiutil, Page, user
from MoinMoin import script
from MoinMoin.action import AttachFile
from MoinMoin.logfile import editlog, LogMissing

url_prefix_static = "."
logo_html = '<img src="logo.png">'
HTML_SUFFIX = ".html"

timestamp_text = u"""This is a MoinMoin timestamp file.
Please delete it to rebuild all pages.
This page dump was last created at:
%s
"""
page_template = u'''<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=%(charset)s">
<title>%(pagename)s</title>
<link rel="stylesheet" type="text/css" media="all" charset="utf-8" href="%(theme)s/css/common.css">
<link rel="stylesheet" type="text/css" media="screen" charset="utf-8" href="%(theme)s/css/screen.css">
<link rel="stylesheet" type="text/css" media="print" charset="utf-8" href="%(theme)s/css/print.css">
<style type="text/css">
ul.pagetitle{
  display: inline;
  margin: 0;
  padding: 0;
  font-size: 1.5em;
}
li.pagetitle{
  display: inline;
  margin: 0;
}
td.noborder {
  border: 0;
}
</style>
</head>
<body>
<table>
<tr>
<td class="noborder">
%(logo_html)s
</td>
<td class="noborder">
<ul class="pagetitle">
<li class="pagetitle"><a class="backlink">%(pagename)s</a>
</ul>
<br><br>
%(navibar_html)s
</td>
</tr>
</table>
<hr>
<div id="page">
%(pagehtml)s
</div>
<div id="attachments">
%(attachments_html)s
</div>
<hr>
%(timestamp)s
</body>
</html>
'''

def _attachment_fn(outputdir, pagename, filename=''):
    return os.path.join(outputdir, "attachments", wikiutil.quoteWikinameFS(pagename), filename.encode(config.charset))

def _attachment(request, pagename, filename, outputdir, copy=False, **kw):
    filename = filename.encode(config.charset)
    source_dir = AttachFile.getAttachDir(request, pagename)
    source_file = os.path.join(source_dir, filename)
    dest_dir = os.path.join(outputdir, "attachments", wikiutil.quoteWikinameFS(pagename))
    dest_file = os.path.join(dest_dir, filename)
    dest_url = "attachments/%s/%s" % (wikiutil.quoteWikinameFS(pagename), wikiutil.url_quote(filename))
    if os.access(source_file, os.R_OK):
        if copy:
            if not os.access(dest_dir, os.F_OK):
                try:
                    os.makedirs(dest_dir)
                except OSError, err:
                    if err.errno != errno.EEXIST:
                        script.fatal("Cannot create attachment directory '%s'" % dest_dir)
            elif not os.path.isdir(dest_dir):
                script.fatal("'%s' is not a directory" % dest_dir)

            script.log('Writing "%s" attachment "%s"...' % (pagename, filename))
            shutil.copyfile(source_file, dest_file)
        return dest_url
    else:
        return ""

def fatal_hook(filename, fatal, msgtext):
    os.remove(filename)
    fatal(msgtext)

class PluginScript(script.MoinScript):
    """\
Purpose:
========
This tool allows you to dump MoinMoin wiki pages to static HTML files.

Detailed Instructions:
======================
General syntax: moin [options] export dump [dump-options]

[options] usually should be:
    --config-dir=/path/to/my/cfg/ --wiki-url=http://wiki.example.org/

[dump-options] see below:
    0. You must run this script as owner of the wiki files, usually this is the
       web server user.

    1. To dump all the pages on the wiki to the directory '/mywiki'
       moin ... export dump --target-dir=/mywiki

    2. To dump all the pages readable by 'JohnSmith' on the wiki to the directory
       '/mywiki'
       moin ... export dump --target-dir=/mywiki --username JohnSmith
"""

    def __init__(self, argv=None, def_values=None):
        script.MoinScript.__init__(self, argv, def_values)
        self.parser.add_option(
            "-t", "--target-dir", dest = "target_dir",
            help = "Write html dump to DIRECTORY"
        )
        self.parser.add_option(
            "-u", "--username", dest = "dump_user",
            help = "User the dump will be performed as (for ACL checks, etc)"
        )

    def mainloop(self):
        """ moin-dump's main code. """

        # Prepare output directory
        if not self.options.target_dir:
            script.fatal("you must use --target-dir=/your/output/path to specify the directory we write the html files to")
        outputdir = os.path.abspath(self.options.target_dir)
        try:
            os.mkdir(outputdir)
            script.log("Created output directory '%s'!" % outputdir)
        except OSError, err:
            if err.errno != errno.EEXIST:
                script.fatal("Cannot create output directory '%s'!" % outputdir)

        # Insert config dir or the current directory to the start of the path.
        config_dir = self.options.config_dir
        if config_dir and os.path.isfile(config_dir):
            config_dir = os.path.dirname(config_dir)
        if config_dir and not os.path.isdir(config_dir):
            script.fatal("bad path given to --config-dir option")
        sys.path.insert(0, os.path.abspath(config_dir or os.curdir))

        self.init_request()
        request = self.request

        # fix script_root so we get relative paths in output html
        request.script_root = url_prefix_static

        # use this user for permissions checks
        request.user = user.User(request, name=self.options.dump_user)

        pages = request.rootpage.getPageList(user='', exists=0) # get list of all pages in wiki

        # Check the last update timestamp
        timestamp_file = os.path.join(outputdir, 'moin-last-update')
        try:
            with open(timestamp_file) as f:
                timestamp_value = long(f.read().splitlines()[-1])
        except IOError, err:
            timestamp_value = 0
            if err.errno != errno.ENOENT:
                script.fatal("Cannot check last update time of '%s' (%s)!" % (timestamp_file, str(err)))

        # Create a new timestamp to use if successful
        log = editlog.EditLog(request)
        try: new_timestamp_value = log.date()
        except LogMissing: new_timestamp_value = 0
        new_timestamp_file = timestamp_file + '.new'
        with open(new_timestamp_file, 'w') as f:
            f.write(timestamp_text % new_timestamp_value)

        # Fatal errors should delete the new timestamp file
        script_fatal = script.fatal
        script.fatal = lambda msgtext: fatal_hook(new_timestamp_file, script_fatal, msgtext)

        # Get a list of pages that need actions
        attachments = dict()
        if timestamp_value:
            pages = set()
            for line in log:
                if line.ed_time_usecs <= timestamp_value:
                    continue
                elif line.action in ('ATTNEW', 'ATTDEL'):
                    if line.pagename not in attachments:
                        attachments[line.pagename] = {}
                    attachments[line.pagename][line.extra] = line.action
                elif line.action == 'SAVE/RENAME':
                    attachment_from = _attachment_fn(outputdir, line.extra)
                    attachment_to = _attachment_fn(outputdir, line.pagename)
                    try:
                        os.rename(attachment_from, attachment_to)
                    except OSError, err:
                        if err.errno != errno.ENOENT:
                            script.fatal('Cannot move attachments from "%s" to "%s" (%s)!' % (line.extra, line.pagename, str(err)))
                    else:
                        script.log('Moving attachments from "%s" to "%s"' % (line.extra, line.pagename))
                    pages.add(line.extra)
                pages.add(line.pagename)
            pages = list(pages)

        wikiutil.quoteWikinameURL = lambda pagename, qfn=wikiutil.quoteWikinameFS: (qfn(pagename) + HTML_SUFFIX)

        AttachFile.getAttachUrl = lambda pagename, filename, request, **kw: _attachment(request, pagename, filename, outputdir, **kw)

        errfile = os.path.join(outputdir, 'error.log')
        errlog = open(errfile, 'w')
        errcnt = 0

        page_front_page = wikiutil.getLocalizedPage(request, request.cfg.page_front_page).page_name
        page_title_index = wikiutil.getLocalizedPage(request, 'TitleIndex').page_name
        page_word_index = wikiutil.getLocalizedPage(request, 'WordIndex').page_name

        navibar_html = ''
        for p in [page_front_page, page_title_index, page_word_index]:
            navibar_html += '[<a href="%s">%s</a>]&nbsp;' % (wikiutil.quoteWikinameURL(p), wikiutil.escape(p))

        # Re-render the title and word indicies if anything changed
        if new_timestamp_value > timestamp_value:
            pages = list(set(pages+[page_title_index, page_word_index]))

        if self.options.page: # did user request a particular page or group of pages?
            try:
                namematch = re.compile(self.options.page)
                pages = [page for page in pages if namematch.match(page)]
                if not pages:
                    pages = [self.options.page]
            except:
                pages = [self.options.page]

        # Render the pages in alphabetical order
        pages.sort()

        urlbase = request.url # save wiki base url
        for pagename in pages:
            # Process attachments for this page
            copy_attachments = []
            delete_attachments = []
            if pagename in attachments:
                for filename, action in attachments[pagename].items():
                    if action == 'ATTNEW':
                        copy_attachments.append(filename)
                    elif action == 'ATTDEL':
                        delete_attachments.append(filename)
            elif not timestamp_value:
                copy_attachments = AttachFile._get_files(request, pagename)
            for filename in copy_attachments:
                _attachment(request, pagename, filename, outputdir, copy=True)
            for filename in delete_attachments:
                try:
                    os.remove(_attachment_fn(outputdir, pagename, filename))
                except OSError, err:
                    if err.errno != errno.ENOENT:
                        script.fatal('Cannot remove "%s" attachment "%s" (%s)!' % (pagename, filename, str(err)))
                else:
                    script.log('Removed "%s" attachment "%s"...' % (pagename, filename))

            # we have the same name in URL and FS
            file = wikiutil.quoteWikinameURL(pagename)
            filepath = os.path.join(outputdir, file)
            exists = os.path.exists(filepath)
            request.url = urlbase + pagename # add current pagename to url base
            page = Page.Page(request, pagename)
            missing = not page.exists()
            unreadable = not request.user.may.read(pagename)
            if missing or unreadable:
                try:
                    os.remove(filepath)
                except OSError, err:
                    if err.errno != errno.ENOENT:
                        script.fatal("Cannot remove '%s' (%s)!" % (file, str(err)))
                else:
                    script.log('Removed "%s"...' % pagename)
                continue
            try:
                script.log('Writing "%s"...' % pagename)
                pagehtml = ''
                request.page = page
                try:
                    request.reset()
                    pagehtml = request.redirectedOutput(page.send_page, count_hit=0, content_only=1)
                    attachments_html = AttachFile._build_filelist(request, pagename, 0, 1, downloadonly=True)
                    if attachments_html: attachments_html = '<h2>Attached Files</h2>' + attachments_html
                except Exception:
                    errcnt = errcnt + 1
                    print >> sys.stderr, "*** Caught exception while writing page!"
                    print >> errlog, "~" * 78
                    print >> errlog, pagename
                    import traceback
                    traceback.print_exc(None, errlog)
                except:
                    os.remove(new_timestamp_file)
                    raise
            finally:
                timestamp = time.strftime("%Y-%m-%d %H:%M")
                fileout = codecs.open(filepath, 'w', config.charset)
                fileout.write(page_template % {
                    'charset': config.charset,
                    'pagename': pagename,
                    'pagehtml': pagehtml,
                    'attachments_html': attachments_html,
                    'logo_html': logo_html,
                    'navibar_html': navibar_html,
                    'timestamp': timestamp,
                    'theme': request.cfg.theme_default,
                })
                fileout.close()

        # copy FrontPage to "index.html"
        indexpage = page_front_page
        if self.options.page:
            indexpage = pages[0] # index page has limited use when dumping specific pages, but create one anyway
        shutil.copyfile(
            os.path.join(outputdir, wikiutil.quoteWikinameFS(indexpage) + HTML_SUFFIX),
            os.path.join(outputdir, 'index' + HTML_SUFFIX)
        )

        errlog.close()
        if errcnt:
            print >> sys.stderr, "*** %d error(s) occurred, see '%s'!" % (errcnt, errfile)
            os.remove(new_timestamp_file)
        else:
            os.rename(new_timestamp_file, timestamp_file)

        # Restore the script.fatal hook
        script.fatal = script_fatal

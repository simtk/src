# -*- coding: iso-8859-1 -*-
"""
    MoinMoin - verify account action

    @copyright: 2012 Steve McIntyre
    @license: GNU GPL, see COPYING for details.
"""

from MoinMoin import user, wikiutil
from MoinMoin.Page import Page
from MoinMoin.widget import html
from MoinMoin.auth import MoinAuth

def execute(pagename, request):
    found = False
    for auth in request.cfg.auth:
        if isinstance(auth, MoinAuth):
            found = True
            break

    if not found:
        # we will not have linked, so forbid access
        request.makeForbidden(403, 'No MoinAuth in auth list')
        return

    page = Page(request, "FrontPage")
    _ = request.getText

    if not request.cfg.require_email_verification:
        result = _("Verification not configured!")
        request.theme.add_msg(result, "error")
        return page.send_page()

    uid = request.values.get('i', None)
    verify = request.values.get('v', None)

    # Grab user profile
    theuser = user.User(request, id=uid)

    # Compare the verification code
    if not theuser.valid:
        result = _("Unable to verify user account i=%s v=%s") % (uid, verify)
        request.theme.add_msg(result, "error")
        return page.send_page()

    if not theuser.account_verification:
        result = _("User account has already been verified!")
        request.theme.add_msg(result, "error")
        return page.send_page()

    if theuser.account_verification != verify:
        result = _("Unable to verify user account i=%s v=%s") % (uid, verify)
        request.theme.add_msg(result, "error")
        return page.send_page()

    # All looks sane. Mark verification as done, save data
    theuser.account_verification = ""
    theuser.save()

    loginlink = request.page.url(request, querystr={'action': 'login'})
    result = _('User account verified! You can use this account to <a href="%s">login</a> now...' % loginlink)
    request.theme.add_msg(result, "dialog")
    return page.send_page()


#!/usr/bin/env python

import sys
import os
sys.path.append("../tools")
import mergejs

if len(sys.argv) <= 1:
    print "Invalid number of arguments."
    print "buildpages.py path"
    quit()

have_compressor = None
try:
    import jsmin
    have_compressor = "jsmin"
except ImportError:
    try:
        import minimize
        have_compressor = "minimize"
    except Exception, E:
        print E
        pass

path = sys.argv[1]

print "JS Page Compressor - Modified for cartografur"
print "Path: " + path

for root, dirs, files in os.walk(path):
    for filename in files:
        if not filename.startswith("."):
            filepath = os.path.join(path, filename)
            filepath = filepath.replace("\\", "/")
            print 'File: ' + filepath

            data = file(filepath).read()

            if have_compressor == "jsmin":
                print "Compressing using jsmin."
                minimized = jsmin.jsmin(data)
            elif have_compressor == "minimize":
                print "Compressing using minimize."
                minimized = minimize.minimize(data)
            else: # fallback
                print "Not compressing, no compressor found."
                minimized = data

            print "Writing to %s." % filepath
            file(filepath, "w").write(minimized)

print "Done."

#!/usr/bin/env python

import sys
sys.path.append("../tools")
import mergejs

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

sourceDirectory = "../lib"
configFilename  = "full.cfg"
outputFilename  = "OpenLayers.js"
license         = "license.txt"

if len(sys.argv) > 1:
    configFilename = sys.argv[1]
    extension = configFilename[-4:]

    if extension  != ".cfg":
        configFilename = sys.argv[1] + ".cfg"

if len(sys.argv) > 2:
    outputFilename = sys.argv[2]

if len(sys.argv) > 3:
    sourceDirectory = sys.argv[3]
    
if len(sys.argv) > 4:
    license = sys.argv[4]

print "JS Compressor - Modified for cartografur"
print "Source directory: " + sourceDirectory
print "Config Filename:  " + configFilename
print "Output Filename:  " + outputFilename

print "Merging libraries."
merged = mergejs.run(sourceDirectory, None, configFilename)
if have_compressor == "jsmin":
    print "Compressing using jsmin."
    minimized = jsmin.jsmin(merged)
elif have_compressor == "minimize":
    print "Compressing using minimize."
    minimized = minimize.minimize(merged)
else: # fallback
    print "Not compressing."
    minimized = merged 
print "Adding license file."
minimized = file(license).read() + minimized

print "Writing merged to %s.merged.js." % outputFilename
file(outputFilename + '.merged.js', "w").write(merged)
print "Writing to %s." % outputFilename
file(outputFilename, "w").write(minimized)

print "Done."

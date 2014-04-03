# Wordpress JSON

This Wordpress plugin was part of the [TainoApp](http://ricardoalcocer.com/tainoapp) project.  The concept of this plugin was to not only expose categories and posts like the typical JSON plugin for Wordpress, but to allow have a full-blown JSON-based API on top of Wordpress.

I'm releasing the plugin as-is.  Haven't tested in a while, but I know it still works because there are websites currently running it.

Still to this date I keep hearing about people trying to get data from Wordpress and publish it as mobile apps.  That was the idea of this plugin, there's a lot of work done and I would love it to keep growing.

After installing this plugin, you'll be able to access many custom aspects of Wordpress and the architecture allow you to provide access to pretty much anything.

>*I know there's a lot of cleaning up to do and I hope I'm not using any deprecated functions or db fields*
 
# Installation
This is a Wordpress plugin, so add it as you would any other plugin

# Usage
I'll expand this section as soon as I can get a Wordpress installation and install it myself.

The Plugin provides an Admin panel accesible from the Wordpress Dashboard.  After everything is configured, then you're ready to interact with the plugin from the url.  The plugin also adds custom fields to each post.  I'll document everything when I have time, or if you figure it out, please help me document it :)

# How does it work?
What the plugin does is it attaches itself to Wordpress' index.php bootstrapping process and looks for the tainoapp variable.  If that variable exists, then it grabs it values and tells Wordpress to forget about everything and the plugin takes over.

# License
Released under MIT - [http://alco.mit-license.org](http://alco.mit-license.org
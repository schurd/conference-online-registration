This page describes how the Internationalization (I18N) of the online registration works.

# Introduction #

This project uses the gettext I18N system. If you want to learn more about the gettext software, please have a look here: http://www.gnu.org/software/gettext/
A good and verbose introduction to gettext with php ypu will find here:
http://phpmagazin.de/itr/online_artikel/psecom,id,874,nodeid,62,_language,de.html

# Details #

For some reason the (in PHP) compiled in gettext did not work. So I used the php-gettext paket from Danilo Šegan.

Here a some steps how to create .po files:
  * To create an empty .po file we need to run the command
`xgettext -L PHP --keyword='T_' *.php`
  * This file we can send to our translators. The translators will enter the text in their language and send back the .po file. For this purpose we recommend to use the poeditor, which is a purpose-built editor for .po files. It makes it very simple to edit .po files. See http://www.poedit.net/ for more information about poedit.
  * Once we have got back a language file from a translator we need to put it into our system. There, we need to create a .mo file with the command msgfmt `msgfmt language.po`
  * If there is already a .po file for a specific language, and you only need to update this file with some new or modfied texts, you need to run the following command:
`xgettext -j -o locale/de/deutsch.po -L PHP --keyword='T_' *.php`
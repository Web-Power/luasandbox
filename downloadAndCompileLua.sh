#!/bin/sh
mkdir /tmp/luaCompile
cd /tmp/luaCompile
rm -Rf ./*
wget http://www.lua.org/ftp/lua-5.1.5.tar.gz
tar -xzf lua-5.1.5.tar.gz
cd lua-5.1.5
patch -p1 << "EOF"
diff -rupN test//src/Makefile lua-5.1.5//src/Makefile
--- test//src/Makefile	2012-02-13 21:41:22.000000000 +0100
+++ lua-5.1.5//src/Makefile	2012-11-01 08:39:24.440378217 +0100
@@ -8,7 +8,7 @@
 PLAT= none
 
 CC= gcc
-CFLAGS= -O2 -Wall $(MYCFLAGS)
+CFLAGS= -O2 -Wall -fPIC $(MYCFLAGS)
 AR= ar rcu
 RANLIB= ranlib
 RM= rm -f
EOF
make linux
sudo make install
sudo ln -s /usr/local/lib/liblua.a /usr/lib/liblua.a
sudo ln -s /usr/local/include /usr/include/lua
sudo ln -s /usr/include/lua /usr/include/lua/include

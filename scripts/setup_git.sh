#!/bin/bash

# Clone phpBB repo
git clone git@github.com:phpbb/phpbb.git
git clone git@github.com:phpbb/phpbb-app.git
git clone git@github.com:phpbb/phpbb-core.git
cd phpbb
git remote add phpbb-app git@github.com:phpbb/phpbb-app.git
git remote add phpbb-core git@github.com:phpbb/phpbb-core.git
git fetch phpbb-app
git fetch phpbb-core
# Set up branches that are needed by the splitter
git checkout -b 3.0.x origin/3.0.x
git checkout -b 3.1.x origin/3.1.x
git checkout -b 3.2.x origin/3.2.x

#!/bin/bash

# Check if file for updating tags exists
if [ -f "$PWD/../cache/.split_3.0.x" ] && [ ! -f "$PWD/../cache/.split_3.0.x.lock" ]; then
	target_branch="3.0.x"
elif [ -f "$PWD/../cache/.split_3.1.x" ] && [ ! -f "$PWD/../cache/.split_3.1.x.lock" ]; then
    target_branch="3.1.x"
elif [ -f "$PWD/../cache/.split_3.2.x" ] && [ ! -f "$PWD/../cache/.split_3.2.x.lock" ]; then
    target_branch="3.2.x"
elif [ -f "$PWD/../cache/.split_master" ] && [ ! -f "$PWD/../cache/.split_master.lock" ]; then
    target_branch="master"
fi

# Exit if we don't have a target branch
if [ -z ${target_branch} ]; then
    exit 0
fi

phpbb_repo_exists=true
phpbb_app_exists=true
phpbb_core_exists=true
# Check if phpBB repo exists
if [ ! -d "$PWD/phpbb/.git" ]; then
	echo "phpBB repo has not been set up in phpbb subfolder."
	phpbb_repo_exists=false
fi

# Check if phpBB app repo exists
if [ ! -d "$PWD/phpbb-app/.git" ]; then
	echo "phpBB app repo has not been set up in phpbb subfolder."
	phpbb_app_exists=false
fi
# Check if phpBB core repo exists
if [ ! -d "$PWD/phpbb-core/.git" ]; then
	echo "phpBB core repo has not been set up in phpbb subfolder."
	phpbb_core_exists=false
fi

if [ "$phpbb_repo_exists" = false ] || [ "$phpbb_app_exists" = false ] || [ "$phpbb_core_exists" = false ] ; then
	exit 1
fi

# Create lock file
touch "$PWD/../cache/.split_${target_branch}.lock"

# Run splitter for splitting to phpbb-app
$PWD/splitsh-lite --prefix=phpBB/ --origin=origin/${target_branch} --target=phpbb-app/${target_branch} --path=phpbb/ --git="<1.8.2"

# Run splitter for splitting to phpbb-core
if [ "$target_branch" = "3.1.x" ] || [ "$target_branch" = "3.2.x" ] || [ "$target_branch" = "master" ]; then
    $PWD/splitsh-lite --prefix=phpBB/phpbb --origin=origin/${target_branch} --target=phpbb-core/${target_branch} --path=phpbb/ --git="<1.8.2"
fi

# Push tags
cd phpbb
git fetch
git reset --hard && git checkout ${target_branch} && git reset --hard phpbb-app/${target_branch} && git push phpbb-app ${target_branch}
if [ "$target_branch" = "3.1.x" ] || [ "$target_branch" = "3.2.x" ] || [ "$target_branch" = "master" ]; then
    git checkout ${target_branch} && git reset --hard phpbb-core/${target_branch} && git push phpbb-core ${target_branch}
fi
cd ..

# Delete lock file and split file
rm "$PWD/../cache/.split_${target_branch}.lock"
rm "$PWD/../cache/.split_${target_branch}"

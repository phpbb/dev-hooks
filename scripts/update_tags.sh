#!/bin/bash

# Check if file for updating tags exists
if [ ! -f "$PWD/../cache/.split_tags" ] || [ -f "$PWD/../cache/.split_tags.lock" ]; then
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
touch "$PWD/../cache/.split_tags.lock"

# Get phpBB tag list
cd phpbb
git fetch
mapfile -t phpbb_tags < <(git tag -l "release-*")

# Get phpbb-apps tag list
cd ../phpbb-app
git fetch
mapfile -t phpbb_app_tags < <(git tag -l "release-*")

# Get phpbb-core tag list
cd ../phpbb-core
git fetch
mapfile -t phpbb_core_tags < <(git tag -l "release-*")
cd ..

# Loop through tags and check if any are missing in app or core
for tag in "${phpbb_tags[@]}"
do
    if [[ ! " ${phpbb_app_tags[@]} " =~ " ${tag} " ]]; then
        # Skip unsupported tags
        if [[ "$tag" =~ ^release-2 ]]; then
            continue
        fi
        tag_commit=`$PWD/splitsh-lite --prefix=phpBB/ --origin=refs/tags/${tag} --path=phpbb/ --git="<1.8.2"  2> /dev/null`
        tag_msg=`cd phpbb && git for-each-ref refs/tags/${tag} --format='%(contents)' && cd ..`
        cd phpbb-app
        if git cat-file -e ${tag_commit}^{commit} 2> /dev/null; then
            git tag -a ${tag} ${tag_commit} -m "${tag_msg}"
        fi
        cd ..
    fi

    if [[ ! " ${phpbb_core_tags[@]} " =~ " ${tag} " ]]; then
        # Skip unsupported tags
        if [[ "$tag" =~ ^release-2|^release-3\.0 ]]; then
            continue
        fi
        tag_commit=`$PWD/splitsh-lite --prefix=phpBB/phpbb --origin=refs/tags/${tag} --path=phpbb/ --git="<1.8.2"  2> /dev/null`
        tag_msg=`cd phpbb && git for-each-ref refs/tags/${tag} --format='%(contents)' && cd ..`
        cd phpbb-core
        if git cat-file -e ${tag_commit}^{commit} 2> /dev/null; then
            git tag -a ${tag} ${tag_commit} -m "${tag_msg}"
        fi
        cd ..
    fi
done

# Push tags
cd phpbb-app && git push origin --tags && cd ..
cd phpbb-core && git push origin --tags && cd ..

# Delete lock file and split file
rm "$PWD/../cache/.split_tags.lock"
rm "$PWD/../cache/.split_tags"

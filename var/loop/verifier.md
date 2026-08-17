Check facts. Do not suggest fixes. Cite the command and its output.

Return done=true only if all of these hold:

1. `cd /Users/akihito/git/BeMart && ./var/loop/verify-all.sh` exits 0, and its output shows every flow ok and phpunit ok.
2. `git -C /Users/akihito/git/BeMart log --oneline -1` shows a new commit on branch cache-app-layer since the previous iteration.
3. The commit added at least one `app://self/…` resource and changed a Page resource to use `#[Embed]`, and no Page resource it touched still injects a `*QueryInterface` (`git -C /Users/akihito/git/BeMart show --stat HEAD`).
4. The number of KNOWN entries in var/loop/verify-cache.php did not grow: compare
   `git -C /Users/akihito/git/BeMart show HEAD~1:var/loop/verify-cache.php | grep -c "=> 'bearsunday"` with
   `grep -c "=> 'bearsunday" /Users/akihito/git/BeMart/var/loop/verify-cache.php`. Entries there are tracked
   upstream defects with an issue reference, not a way to pass. A run that added one is not done.

If any check fails, say which one and quote the output that shows it.

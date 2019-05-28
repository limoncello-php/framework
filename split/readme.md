Splitting out the project components is done with [git split](https://github.com/dflydev/git-subsplit).

#### How to add a new component

- Place component under `components` folder.
- Commit and push the code to github.
- Create a new repository in [limoncello-php-dist](https://github.com/limoncello-php-dist);
- Update `components.sh` file.
- Run one of the split scripts.
- Submit the component to [packagist.org](https://packagist.org/).
- Add a hook on github to packagist.org, set up github wiki, issues, and etc.

#### How to publish new rolling release

Remove a tag for the rolling release

```bash
$ git push origin :refs/tags/0.10.0
```

Go to project releases page on github and start editing the release. When done click "Publish release" button. 

Run publish script "with-tags"

```bash
$ ./split-with-tags.sh
```

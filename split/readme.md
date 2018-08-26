Splitting out the project components is done with [git split](https://github.com/dflydev/git-subsplit).

#### How to add a new component

- Place component under `components` folder.
- Commit and push the code to github.
- Create a new repository in [limoncello-php-dist](https://github.com/limoncello-php-dist);
- Update `components.sh` file.
- Run one of the split scripts.
- Submit the component to [packagist.org](https://packagist.org/).
- Add a hook on github to packagist.org, set up github wiki, issues, and etc.

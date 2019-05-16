#!/usr/bin/node
/* eslint-disable class-methods-use-this, no-await-in-loop, no-continue, no-new, no-process-exit */
/**
 * Usage: node dumplist.js [check|testmd5|testsha1|updatesha1|generate|update|touchdir]
 *
 * From the shadows. We shall rise...
 *
 * @Author  Jorge Oliveira (NewEraCracker)
 * @Date    May 16th 2019
 * @License Public Domain
 * @Version 0.0.1-node
 */

const [crypto, fs, { promisify }] = [require('crypto'), require('fs'), require('util')];
const [access, readdir, readFile, stat, utimes, writeFile] = [promisify(fs.access), promisify(fs.readdir), promisify(fs.readFile), promisify(fs.stat), promisify(fs.utimes), promisify(fs.writeFile)];

const die = (mssg) => {
  console.error(mssg);
  process.exit(1);
};

const file_exists = (path) => {
  return access(path).then(() => true, () => false);
};

const is_writable = (path) => {
  return access(path, fs.constants.W_OK).then(() => true, () => false);
};

const filemtime = async (path) => {
  const stats = await stat(path);
  return Math.floor(stats.mtimeMs / 1000);
};

const md5_file = (filename) => {
  return new Promise((resolve, reject) => {
    const [output, input] = [crypto.createHash('md5'), fs.createReadStream(filename)];

    input.on('error', (err) => {
      reject(err);
    });

    output.once('readable', () => {
      resolve(output.read().toString('hex'));
    });

    input.pipe(output);
  });
};

const sha1_file = (filename) => {
  return new Promise((resolve, reject) => {
    const [output, input] = [crypto.createHash('sha1'), fs.createReadStream(filename)];

    input.on('error', (err) => {
      reject(err);
    });

    output.once('readable', () => {
      resolve(output.read().toString('hex'));
    });

    input.pipe(output);
  });
};

const substr_count = (str, char) => {
  let cnt = 0;

  for (let i = 0; i < str.length; i++) {
    if (str[i] === char) { ++cnt; }
  }

  return cnt;
};

const { basename, dirname } = require('path');
const { chdir } = global.process;

/** Utility static methods for dump listing */
class NewEra_DumpListUtil {

  /**
   * This will parse a listfile
   */
  async parse_listfile (filename) {

    const [fileproperties, comment, content] = [{}, {mtime: [], sha1: [], name: []}, {md5: [], name: []}];

    if (!await file_exists(filename)) {
      console.error('Error parsing listfile: File does not exist');
      return false;
    }

    const filecontents = await readFile(filename, {encoding: 'utf8'});

    filecontents.replace(/^; ([0-9]+) ([^\r\n]+)/gm, (...m) => {
      comment.mtime.push(m[1]);
      comment.name.push(m[2]);
    });

    if (!comment.mtime.length)
    {
      console.error('Error parsing listfile: Unable to parse comments');
      return false;
    }

    filecontents.replace(/^([0-9a-f]{32}) ([*][^\r\n]+)/gm, (...m) => {
      content.md5.push(m[1]);
      content.name.push(m[2]);
    });

    if (!content.md5.length) {
      console.error('Error parsing listfile: Unable to parse contents');
      return false;
    }

    if (comment['name'].length === content['name'].length) {

      for (let i = 0; i < comment['name'].length; i++) {

        // Compatibility with files generated by version 1 and/or version 2
        if (comment['name'][i][0] != '*') {
          comment['sha1'][i] = comment['name'][i].substr(0, 40);
          comment['name'][i] = comment['name'][i].substr(41);
        }

        if (comment['name'][i] === content['name'][i]) {

          // Not an hack: We have to remove the asterisk in begining and restore ./ in path for Node.js to be able to work it out
          const file = './' + comment['name'][i].substr(1);

          fileproperties[file] = {
            'mtime': comment['mtime'][i],
            'sha1': comment['sha1'][i] || '',
            'md5': content['md5'][i]
          };
        } else {
          console.error('Error parsing listfile: Invalid entry order');
          return false;
        }
      }
    } else {
      console.error('Error parsing listfile: Invalid entry count');
      return false;
    }

    return fileproperties;
  }

  /** This will generate a listfile */
  generate_listfile (fileproperties) {

    // Init contents of list file
    let [comment, content] = ['', ''];

    // Sort file properties array and walk
    for (const file of Object.keys(fileproperties).sort(NewEra_Compare.prototype.sort_files_by_name)) {
      const properties = fileproperties[file];

      // Not an hack: We have to replace ./ in path by an asterisk for other applications (QuickSFV, TeraCopy...) to be able to work it out
      const filename = '*' + file.substr(2);

      comment += `; ${properties['mtime']} ${(properties['sha1'] ? (properties['sha1'] + ' ') : '')}${filename}\n`;
      content += properties['md5'] + ' ' + filename + "\n";
    }

    return (comment + content);
  }

  /** Array with the paths a dir contains */
  async readdir_recursive (dir = '.', show_dirs = false, ignored = []) {

    // Set types for stack and return value
    const [stack, result] = [[], []];

    // Initialize stack
    stack.push(dir);

    // Pop the first element of stack and evaluate it (do this until stack is fully empty)
    while (dir = stack.shift()) { // eslint-disable-line no-cond-assign

      const files = await readdir(dir);

      for (let path of files) {

        // Prepend dir to current path
        path = dir + '/' + path;

        const stats = await stat(path);

        if (stats.isDirectory()) {

          // Check ignored dirs
          if (Array.isArray(ignored) && ignored.length && ignored.indexOf(path + '/') !== -1) { continue; }

          // Add dir to stack for reading
          stack.push(path);

          // If show_dirs is true, add dir path to result
          if (show_dirs) { result.push(path); }

        } else if (stats.isFile()) {

          // Check ignored files
          if (Array.isArray(ignored) && ignored.length && ignored.indexOf(path) !== -1) { continue; }

          // Add file path to result
          result.push(path);
        }
      }
    }

    // Sort the array using simple ordering
    result.sort();

    // Now we can return it
    return result;
  }
}

/* Useful comparators */
class NewEra_Compare {

  /* Ascending directory sorting by names */
  sort_files_by_name (a, b) {

    /* Equal */
    if (a == b) { return 0; }

    /* Let strcmp decide */
    return (( a > b ) ? 1 : -1 );
  }

  /* Ascending directory sorting by levels and names */
  sort_files_by_level_asc (a, b) {

    /* Equal */
    if (a == b) { return 0; }

    /* Check dir levels */
    const la = substr_count(a, '/');
    const lb = substr_count(b, '/');

    /* Prioritize levels, in case of equality let sorting by names decide */
    return ((la < lb) ? -1 : ((la == lb) ? NewEra_Compare.prototype.sort_files_by_name(a, b) : 1));
  }

  /* Reverse directory sorting by levels and names */
  sort_files_by_level_dsc (a, b) {

    return NewEra_Compare.prototype.sort_files_by_level_asc(b, a);
  }
}

/** Methods used in dump listing */
class NewEra_DumpList {

  /** Construct the object and perform actions */
  constructor (listfile = './filelist.md5', ignored = []) {

    /** The file that holds the file list */
    this.listfile = listfile;

    /** Ignored paths */
    this.ignored = [
      listfile,                      /* List file */
      ('./' + basename(__filename)), /* This file */
      ...ignored                     /* Original ignored array */
     ];

    /** Simple file list array */
    this.filelist = [];

    /** Detailed file list array */
    this.fileproperties = [];

    // Check arguments count
    if (process.argv.length != 3) {
      die('Usage: node ' + basename(__filename) + " [check|testmd5|testsha1|updatesha1|generate|update|touchdir]\n");
    }

    // Change dir
    chdir(dirname(__filename));

    // Process arguments
    switch(process.argv[2]) {
      case 'testmd5':
        this.dumplist_check(true);
        break;
      case 'testsha1':
        this.dumplist_check(false, true);
        break;
      case 'updatesha1':
        this.dumplist_check(false, false, true);
        break;
      case 'check':
        this.dumplist_check(false);
        break;
      case 'generate':
        this.dumplist_generate();
        break;
      case 'update':
        this.dumplist_update();
        break;
      case 'touchdir':
        this.dumplist_touchdir();
        break;
      default:
        die('Usage: node '.basename(__filename) + " [check|testmd5|testsha1|updatesha1|generate|update|touchdir]\n");
    }
  }

  /** Run the check on each file */
  async dumplist_check (testmd5 = false, testsha1 = false, updatesha1 = false) {

    this.filelist = await NewEra_DumpListUtil.prototype.readdir_recursive('.', false, this.ignored);
    this.fileproperties = await NewEra_DumpListUtil.prototype.parse_listfile(this.listfile);

    if (!this.fileproperties) { return; }

    for (const file of this.filelist) {

      // Handle creation case
      if (!this.fileproperties.hasOwnProperty(file)) {
        console.log(`${file} is a new file.`);
        continue;
      }
    }

    for (const file of Object.keys(this.fileproperties)) {
      const properties = this.fileproperties[file];

      // Handle deletion
      if (!await file_exists(file)) {
        console.log(`${file} does not exist.`);
        continue;
      }

      // Handle file modification
      if (await filemtime(file) != properties['mtime']) {
        console.log(`${file} was modified.`);
        continue;
      }

      // Test file MD5 if required
      if (testmd5) {
        const md5 = await md5_file(file);

        if (md5 != properties['md5']) {
          console.log(`${file} Expected MD5: ${properties['md5']} Got: {md5}.`);
          continue;
        }
      }

      // Test file SHA1 if required
      if (testsha1 && properties['sha1']) {
        const sha1 = await sha1_file(file);

        if (sha1 != properties['sha1']) {
          console.log(`${file} Expected SHA1: ${properties['sha1']} Got: ${sha1}.`);
          continue;
        }
      }

      // Migrate SHA1 if required
      if (updatesha1 && !properties['sha1']) {
        const md5 = await md5_file(file);

        if (md5 === properties['md5']) {
          properties['sha1'] = sha1_file(file);
          this.fileproperties[file] = properties;
        } else {
          console.log(`${file} Expected MD5: ${properties['md5']} Got: ${md5}.`);
          continue;
        }
      }
    }

    // Write new file if migrating
    if (updatesha1) {
      const contents = NewEra_DumpListUtil.prototype.generate_listfile(this.fileproperties);
      await writeFile(this.listfile, contents);
    }
  }

  /** Generate dump file listing */
  async dumplist_generate () {

    this.filelist = await NewEra_DumpListUtil.prototype.readdir_recursive('.', false, this.ignored);
    this.fileproperties = {};

    for (const file of this.filelist) {
      this.fileproperties[file] = {
        mtime: await filemtime(file),
        sha1: await sha1_file(file),
        md5: await md5_file(file)
      };
    }

    const contents = NewEra_DumpListUtil.prototype.generate_listfile(this.fileproperties);
    await writeFile(this.listfile, contents);
  }

  /** Update dump file listing */
  async dumplist_update () {

    this.filelist = await NewEra_DumpListUtil.prototype.readdir_recursive('.', false, this.ignored);
    this.fileproperties = await NewEra_DumpListUtil.prototype.parse_listfile(this.listfile);

    if (!this.fileproperties) { return; }

    for (const file of this.filelist) {

      // Handle creation case
      if (!this.fileproperties.hasOwnProperty(file))
      {
        this.fileproperties[file] = {
          'mtime': await filemtime(file),
          'sha1': await sha1_file(file),
          'md5': await md5_file(file)
        };
        continue;
      }
    }

    // Save the keys to remove in case there is file deletion
    const keys_to_remove = [];

    // Handle each file in the properties list
    for (const file of Object.keys(this.fileproperties)) {
      const properties = this.fileproperties[file];

      // Handle deletion (Save it, will delete the keys later)
      if (!await file_exists(file)) {
        keys_to_remove.push(file);
        continue;
      }

      // Handle file modification
      if (await filemtime(file) != properties['mtime']) {
        this.fileproperties["{file}"] = {
          'mtime': await filemtime(file),
          'sha1': await sha1_file(file),
          'md5': await md5_file(file)
        };
        continue;
      }
    }

    // Handle deletion (Delete the keys now)
    if (keys_to_remove.length > 0) {
      for (const key of keys_to_remove) {
        this.fileproperties[key] = null;
        delete this.fileproperties[key];
      }
    }

    const contents = NewEra_DumpListUtil.prototype.generate_listfile(this.fileproperties);
    await writeFile(this.listfile, contents);
  }

  async dumplist_touchdir () {

    // Filelist including directories
    const list = await NewEra_DumpListUtil.prototype.readdir_recursive('.', true, this.ignored);

    // Easier with a bottom to top approach
    list.sort(NewEra_Compare.prototype.sort_files_by_level_dsc);

    // Handle list including directories. Then run
    // another pass with list without directories
    for (let i = 0; i < 2; i++) {

      // Reset internal variables state
      let [dir, time] = [null, null];

      // Handle list
      for (const file of list) {

        // Ignore dir dates on pass two
        if (i === 1 && (await stat(file)).isDirectory()) {
          continue;
        }

        // Blacklist certain names
        if (file.toLowerCase().indexOf('/desktop.ini') !== -1 || file.indexOf('/.') != -1) {
          continue;
        }

        // Reset internal variables state when moving to another dir
        if (dir !== dirname(file)) {
          dir  = dirname(file);
          time = 0;
        }

        // Save current time
        const mtime = await filemtime(file);

        // Only update when mtime is correctly set and higher than time
        // Also check for writability to prevent errors
        if (mtime > 0 && mtime > time && is_writable(dir)) {

          // Save new timestamp
          time = mtime;

          // Update timestamp
          await utimes(dir, time, time);
        }
      }
    }

    // I think we should be OK
    return true;
  }
}

/** Run */
new NewEra_DumpList('./filelist.md5', ['./_incoming/', './.htaccess', './.htpasswd', './index.php']);
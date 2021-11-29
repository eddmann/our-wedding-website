require('./styles.scss');

// @ts-ignore
import TableSort from 'tablesort';

[].forEach.call(document.getElementsByClassName('table-sortable'), (el: Element) => new TableSort(el));

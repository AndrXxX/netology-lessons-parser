"use strict";

/**
 * Класс для переключения состояния парсинга
 */
class ParsingState {
  constructor(button) {
    this.button = button;
    this.btnStartName = 'Начать парсинг';
    this.btnStopName = 'Остановить парсинг';
    this.parsingState = false;
  }

  stopParsing() {
    this.parsingState = false;
    this.button.value = this.btnStartName;
    optionsBtn.classList.remove('hidden');
    progressElement.parentNode.classList.add('hidden');
  }

  startParsing() {
    this.parsingState = true;
    this.button.value = this.btnStopName;
    showErrorsBtn.classList.remove('hidden');
    optionsBtn.classList.add('hidden');
    progressElement.parentNode.classList.remove('hidden');
    info.classList.remove('hidden');
    progress = new Progress(progressElement);
    success.innerText = '';
    errors.innerText = '';
  }

  isNowParsing() {
    return this.parsingState;
  }
}

/**
 * Класс для отслеживания прогресса выполнения парсинга
 */
class Progress {
  constructor(showProgressElement) {
    this.progressElement = showProgressElement;
    this.elements = 0;
    this.finished = 0;
  }

  showProgress() {
    this.progressElement.style.width = `${this.progress}%`;
    this.progressElement.innerText = `${this.progress}%`;
  }

  get progress() {
    if (this.elements === 0) {
      return 0;
    } else {
      if (this.finished < this.elements) {
        return Math.round(this.finished / this.elements * 100);
      } else {
        return 100;
      }
    }
  }

  addElements(num) {
    this.elements += Number.parseInt(num);
  }

  addFinishedElements(num) {
    this.finished += Number.parseInt(num);
    this.showProgress();
  }
}

/**
 * Класс для работы с расписанием
 */
class Timetable {
  constructor() {
    this.dates = [];
  }

  add(seminar) {
    if (!this[seminar.date]) {
      this.dates.push(seminar.date);
      this[seminar.date] = [];
    }

    this[seminar.date].push(seminar);
  };

  convertDate(date) {
    const [month, day, year] = date.split('-');

    return new ExtDate(year, month - 1, day);
  }

  show(date) {
    if (!this[date]) return 'Нет занятий в этот день';

    const dayTimetable = {
      date: this.convertDate(date),
      seminars: { times: []}
    };

    //17:00 ND-11 «Знакомство с терминами SPA, MVC и введение в Angular» - Гильязов
    this[date].forEach(seminar => {
      let time = seminar.time.match(/[0-9]+:[0-9]+/)[0];

      if (dayTimetable.seminars.times.indexOf(time) === -1) {
        dayTimetable.seminars.times.push(time);
        dayTimetable.seminars[time] = [];
      }

      dayTimetable.seminars[time].push(
        {
          time: time,
          courseCode: seminar.courseCode,
          name: seminar.name.replace(/Занятие [0-9]+.[0-9]+. /, ''),
          teacher: seminar.teacher.name
        }
      );
    });

    return dayTimetable;
  }

  showAll() {
    const timetable = [];
    this.dates.sort();
    this.dates.forEach(date => {
      timetable.push(this.show(date));
    });

    return timetable;
  }

  clear() {
    for (let date of this.dates) {
      this[date] = undefined;
    }

    this.dates = [];
  }
}

class ExtDate extends Date {
  constructor(...args) {
    super(...args);
  }
  getRuDayMonth() {
    let months = ['января', 'февраля', 'марта', 'апреля', 'мая',
      'июня', 'июля', 'августа','сентября', 'октября', 'ноября', 'декабря'];
    return `${this.getDate()} ${months[this.getMonth()]}`;
  }
}

const main = document.querySelector('.main');
const options = document.querySelector('.options');
const info = document.querySelector('.info');
const success = document.querySelector('.success');
const errors = document.querySelector('.errors');
const startBtn = document.querySelector('.btn.btn-start');
const optionsBtn = document.querySelector('.btn.btn-options');
const showErrorsBtn = document.querySelector('.btn.btn-show-errors');
const saveBtn = document.querySelector('.btn.btn-save');
const backBtn = document.querySelector('.btn.btn-back');
const progressElement = document.querySelector('.progress');
const parsing = new ParsingState(startBtn);
const courses = document.querySelectorAll('.options .course');
const timetable = new Timetable();
let progress;

/* добавляем обработчики */
startBtn.addEventListener('click', onStartBtnClick);

optionsBtn.addEventListener('click', () => {
  options.classList.remove('hidden');
  main.classList.add('hidden');
});

backBtn.addEventListener('click', () => {
  options.classList.add('hidden');
  main.classList.remove('hidden');
});

saveBtn.addEventListener('click', () => {
  alert('Пока не работает - настройки сохраняются до перезагрузки страницы. Доделаю позже')
});

showErrorsBtn.addEventListener('click', () => {
  if (errors.classList.contains('hidden')) {
    showErrorsBtn.value = 'Скрыть расширенный лог';
  } else {
    showErrorsBtn.value = 'Показать расширенный лог';
  }
  errors.classList.toggle('hidden');
  success.classList.toggle('hidden');
});

for (let course of courses) {
  course.querySelector('.start-num').addEventListener('input', event => {
    course.dataset.startNum = event.target.value;
  });
  course.querySelector('.groups-num').addEventListener('input', event => {
    course.dataset.groupsNum = event.target.value;
  });
}

/**
 * Запускается при нажатии начать/остановить парсинг
 * @param event
 */
function onStartBtnClick(event) {
  if (parsing.isNowParsing()) {
    parsing.stopParsing();
  } else {
    parsing.startParsing();

    for (let course of courses) {
      const name = course.dataset.name;
      const start = Number.parseInt(course.dataset.startNum);
      const groupsNum = Number.parseInt(course.dataset.groupsNum);

      for (let num = start; num < start + groupsNum; num++) {
        progress.addElements(1);
        const post = new FormData();
        post.append('course', name);
        post.append('group', num);

        sendRequest('start_pars.php', responseHandler, 'POST', post);
      }
    }

    optionsBtn.classList.add('hidden');
  }
}

/**
 * Функция отправляет запросы
 * @param url
 * @param responseHandler
 * @param method
 * @param data
 */
function sendRequest(url = '', responseHandler = (data) => data, method = 'POST', data = undefined) {
  let xhr = new XMLHttpRequest();
  xhr.addEventListener('load', (event) => {
    "use strict";
    if (event.target.status === 200) {
      responseHandler(event.target.responseText);
    }
  });

  xhr.open(method, url, true);
  if (data) {
    xhr.send(data);
  } else {
    xhr.send();
  }
}

/**
 * Функция - обработчик результатов выполнения запросов
 * @param data
 */
function responseHandler(data) {
  const decodedData = JSON.parse(data);
  //console.log(decodedData);
  const curGroup = document.createElement('div');

  if (decodedData) {
    curGroup.innerText = `${decodedData.course}-${decodedData.group}`;
    if (decodedData.success) {
      curGroup.innerText += ' / успешно';
      success.appendChild(curGroup);
      courseParser(decodedData);
    } else {
      curGroup.innerText += ` / ${decodedData.errors[0].message}`;
      errors.appendChild(curGroup);
    }
  }

  progress.addFinishedElements(1);
  if (progress.progress === 100) { // если все выполнено
    parsing.stopParsing();

    showTimetable();
  }
}

/**
 * Функция парсит курс и добавляет нужные данные в расписание
 * @param course
 */
function courseParser(course) {
  course.data.blocks.forEach(courseBlock => {
    courseBlock.seminars.forEach(seminar => {
      const today = new ExtDate();
      today.setMinutes(0);
      today.setHours(0);

      if (today <= timetable.convertDate(seminar.date)) {
        seminar.courseCode = `${course.course}-${course.group}`;
        timetable.add(seminar);
      }
    })
  })
}

/**
 * Отображает расписание
 */
function showTimetable() {
  const today = new ExtDate();
  const endPeriod = new ExtDate(today.getTime());
  today.setMinutes(0);
  today.setHours(0);
  endPeriod.setDate(endPeriod.getDate() + 7);
  endPeriod.setMinutes(59);
  endPeriod.setHours(23);

  const output = info.querySelector('.info-output');
  let seminarsList = '';

  timetable.showAll().forEach(date => {
    if (date.date > today && date.date < endPeriod) {
      seminarsList += `\n${date.date.getRuDayMonth()}:\n`;

      date.seminars.times.sort();

      date.seminars.times.forEach(time => {
        // 17:00 ND-11 «Знакомство с терминами SPA, MVC и введение в Angular» - Гильязов
        date.seminars[time].forEach(seminar => {
          seminarsList += `${seminar.time} ${seminar.courseCode} «${seminar.name}» - ${seminar.teacher}\n`;
        });
      });
    }
  });

  output.innerHTML = seminarsList;
}

<template>
  <div class="datepicker">
    <div class="input-group" :class="size ? `input-group-${size}`: null">
      <input
        v-model="val"
        class="form-control datepicker-input"
        type="text"
        :class="{'with-reset-button': clearButton }"
        :name="name"
        :placeholder="placeholder"
        :style="{width:width}"
        ref="dateinput"
        autocomplete="off"
        @focus.stop.prevent="focus"
        :disabled="disabled"
        @blur="close"
      />
      <div class="input-group-append">
        <button class="btn btn-outline-primary" type="button" @click="$refs.dateinput.focus()" tabIndex="-1" :disabled="disabled">
          <i class="fas fa-calendar-alt" />
        </button>

      </div>
    </div>
    <button v-if="clearButton&&val" type="button" class="close" @mousedown.stop.prevent="val = ''">
      <span>&times;</span>
    </button>
    <div v-show="isPopupShowing" class="datepicker-popup" @mousedown.stop.prevent ref="popup">
      <div class="datepicker-inner">
        <div v-show="displayDayView" class="datepicker-body">
          <div class="datepicker-ctrl">
            <span class="datepicker-prev datepicker-btn" aria-hidden="true" @mousedown.stop.prevent="preNextMonthClick(0)">
              <i class="fas fa-chevron-left"></i>
            </span>
            <span class="datepicker-next datepicker-btn" aria-hidden="true" @mousedown.stop.prevent="preNextMonthClick(1)">
              <i class="fas fa-chevron-right"></i>
            </span>
            <p class="datepicker-btn" @mousedown.stop.prevent="switchMonthView">{{ stringifyDayHeader(currDate) }}</p>
          </div>
          <div class="datepicker-calendar">
            <div class="datepicker-weekRange">
              <span v-for="w in text.daysOfWeek" :key="`day-week-${w}`">{{ w }}</span>
            </div>
            <div class="datepicker-dateRange">
              <span
                v-for="(d, i) in dateRange"
                :key="`day-day-${i}`"
                :class="d.sclass"
                @mousedown.stop="daySelect(d)"
              >{{ d.text }}</span>
            </div>
          </div>
        </div>
        <div v-show="displayMonthView" class="datepicker-body">
          <div class="datepicker-ctrl">
            <span class="datepicker-prev datepicker-btn" aria-hidden="true" @mousedown.stop.prevent="preNextYearClick(0)">
              <i class="fas fa-chevron-left"></i>
            </span>
            <span class="datepicker-next datepicker-btn" aria-hidden="true" @mousedown.stop.prevent="preNextYearClick(1)">
              <i class="fas fa-chevron-right"></i>
            </span>
            <p class="datepicker-btn" @mousedown.stop.prevent="switchDecadeView">{{ stringifyYearHeader(currDate) }}</p>
          </div>
          <div class="datepicker-calendar">
            <div class="datepicker-monthRange">
              <template v-for="(m, index) in text.months">
                <span
                  :key="`month-month-${index}`"
                  :class="{'datepicker-dateRange-item-active': val && (text.months[parse(val).getMonth()] === m) && (currDate.getFullYear() === parse(val).getFullYear())}"
                  @mousedown.stop.prevent="monthSelect(index)"
                  v-text="m.substr(0,3)"
                />
              </template>
            </div>
          </div>
        </div>
        <div v-show="displayYearView" class="datepicker-body">
          <div class="datepicker-ctrl">
            <span class="datepicker-prev datepicker-btn" aria-hidden="true" @mousedown.stop.prevent="preNextDecadeClick(0)">
              <i class="fas fa-chevron-left"></i>
            </span>
            <span class="datepicker-next datepicker-btn" aria-hidden="true" @mousedown.stop.prevent="preNextDecadeClick(1)">
              <i class="fas fa-chevron-right"></i>
            </span>
            <p>{{ stringifyDecadeHeader(currDate) }}</p>
          </div>
          <div class="datepicker-calendar">
            <div class="datepicker-monthRange decadeRange">
              <template v-for="decade in decadeRange">
                <span
                  :key="`decade-${decade.text}`"
                  :class="{'datepicker-dateRange-item-active': val && (parse(val).getFullYear() === decade.text)}"
                  @mousedown.stop.prevent="yearSelect(decade.text)"
                  v-text="decade.text"
                />
              </template>
            </div>
          </div>
        </div>
      </div>
      <div v-if="extra" class="datepicker-inner">
        <div class="datepicker-body">
            <span class="datepicker-btn" @mousedown.stop="selectToday">Hari Ini</span>
            <span class="datepicker-btn" @mousedown.stop="selectNextWeek">Minggu Hadapan</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { isBefore } from "date-fns";

const utils = {
  translations(lang = "en") {
    let text = {
      daysOfWeek: ["Ah", "Is", "Se", "Ra", "Kh", "Ju", "Sa"],
      limit: "Limit reached ({{limit}} items max).",
      loading: "Loading...",
      minLength: "Min. Length",
      months: [
        "Januari",
        "Februari",
        "Mac",
        "April",
        "Mei",
        "Jun",
        "Julai",
        "Ogos",
        "September",
        "Oktober",
        "November",
        "Disember"
      ],
      notSelected: "Nothing Selected",
      required: "Required",
      search: "Search",
      selected: "{{count}} selected"
    };
    return text;
  },
  getDayCount(year, month) {
    const dict = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    if (month === 1) {
      if (year % 400 === 0 || (year % 4 === 0 && year % 100 !== 0)) {
        return 29;
      }
    }
    return dict[month];
  }
};

export default {
  inject: ['$validator'],
  props: {
    max: String,
    min: String,
    size: {
      type: String,
    },
    value: { type: String },
    format: { default: "dd/MM/yyyy" },
    disabledDaysOfWeek: {
      type: Array,
      default() {
        return [];
      }
    },
    disabled: Boolean,
    width: { type: String },
    clearButton: { type: Boolean, default: false },
    name: { type: String },
    placeholder: { type: String },
    extra: { type: Boolean, default: false },
    lang: { type: String, default: navigator.language },
  },
  data() {
    return {
      currDate: new Date(),
      dateRange: [],
      decadeRange: [],
      displayDayView: false,
      displayMonthView: false,
      displayYearView: false,
      val: this.value || "",
    };
  },
  computed: {
    text() {
      return utils.translations(this.lang);
    },
    disabledDaysArray() {
      return this.disabledDaysOfWeek.map(d => parseInt(d, 10));
    },
    maxDate() {
      return this.parse(this.max);
    },
    minDate() {
      return this.parse(this.min);
    },
    isPopupShowing() {
      return this.displayDayView || this.displayMonthView || this.displayYearView;
    },
  },
  watch: {
    currDate() {
      this.getDateRange();
    },
    format() {
      this.val = this.stringify(this.currDate);
    },
    val(val, old) {
      this.$emit("input", val);
      this.$emit("changed", val);
    },
    value(val) {
      if (this.val !== val) {
        this.val = val;
      }
    }
  },

  mounted() {
    this.$emit("child-created", this);
    this.currDate = this.parse(this.val) || this.parse(new Date());
    document.body.addEventListener('keydown', this.navigateLeftRight);
    window.addEventListener('scroll', this.setPopupPosition);
  },

  beforeDestroy() {
    document.body.removeEventListener('keydown', this.navigateLeftRight);
    window.removeEventListener('scroll', this.setPopupPosition);
  },


  methods: {
    close() {
      this.displayDayView = this.displayMonthView = this.displayYearView = false;
    },

    focus() {
      if (this.disabled) {
        return;
      }

      this.currDate = this.parse(this.val) || this.parse(new Date());
      if (this.displayMonthView || this.displayYearView) {
        this.displayDayView = false;
      } else {
        this.displayDayView = !this.displayDayView;
      }

      this.setPopupPosition();
    },

    setPopupPosition() {
      if (!this.isPopupShowing) {
        return;
      }

      this.$nextTick(() => {
        const docBoundary = document.documentElement.getBoundingClientRect();
        const popup = this.$refs.popup;
        const input = this.$refs.dateinput;

        const popupBoundary = popup.getBoundingClientRect();
        const viewportHeight = docBoundary.height + docBoundary.top;

        let positionTop = input.getBoundingClientRect().bottom;

        if (positionTop + popupBoundary.height > viewportHeight) {
          const overflow = (positionTop + popupBoundary.height) - viewportHeight;

          positionTop = positionTop - overflow;
        }

        $(popup).css({ 'top': positionTop + 'px' })

      })
    },

    navigateLeftRight(event) {
      if (!this.displayDayView && !this.displayMonthView && !this.displayYearView) {
        return;
      }

      // press left
      if (this.displayDayView) {
        if (event.keyCode == 37) {
          this.preNextMonthClick(0);
        } else if (event.keyCode == 39) {
          this.preNextMonthClick(1);
        }
      }
      else if (this.displayMonthView) {
        if (event.keyCode == 37) {
          this.preNextYearClick(0);
        } else if (event.keyCode == 39) {
          this.preNextYearClick(1);
        }
      }
      else if (this.displayYearView) {
        if (event.keyCode == 37) {
          this.preNextDecadeClick(0);
        } else if (event.keyCode == 39) {
          this.preNextDecadeClick(1);
        }
      }
    },

    preNextDecadeClick(flag) {
      const year = this.currDate.getFullYear();
      const months = this.currDate.getMonth();
      const date = this.currDate.getDate();

      if (flag === 0) {
        this.currDate = new Date(year - 10, months, date);
      } else {
        this.currDate = new Date(year + 10, months, date);
      }
    },

    preNextMonthClick(flag) {
      const year = this.currDate.getFullYear();
      const month = this.currDate.getMonth();
      const date = this.currDate.getDate();

      if (flag === 0) {
        const preMonth = this.getYearMonth(year, month - 1);
        this.currDate = new Date(preMonth.year, preMonth.month, date);
      } else {
        const nextMonth = this.getYearMonth(year, month + 1);
        this.currDate = new Date(nextMonth.year, nextMonth.month, date);
      }
    },

    preNextYearClick(flag) {
      const year = this.currDate.getFullYear();
      const months = this.currDate.getMonth();
      const date = this.currDate.getDate();

      if (flag === 0) {
        this.currDate = new Date(year - 1, months, date);
      } else {
        this.currDate = new Date(year + 1, months, date);
      }
    },

    yearSelect(year) {
      this.displayYearView = false;
      this.displayMonthView = true;
      this.currDate = new Date(year, this.currDate.getMonth(), this.currDate.getDate());
    },

    daySelect(day) {
      if (day.sclass.includes( "datepicker-item-disable")) {
        return false;
      } else {
        this.currDate = day.date;
        this.val = this.stringify(this.currDate);
        this.displayDayView = false;
      }
    },

    selectToday() {
      this.currDate = new Date();
      this.val = this.stringify(this.currDate);
      this.close();
    },

    selectNextWeek() {
      const today = new Date();
      const year = today.getFullYear();
      const months = today.getMonth();
      const date = today.getDate();

      this.currDate = new Date(year, months, date + 7);
      this.val = this.stringify(this.currDate);
      this.close();
    },

    switchMonthView() {
      this.displayDayView = false;
      this.displayMonthView = true;
    },

    switchDecadeView() {
      this.displayMonthView = false;
      this.displayYearView = true;
    },

    monthSelect(index) {
      this.displayMonthView = false;
      this.displayDayView = true;
      this.currDate = new Date(this.currDate.getFullYear(), index, this.currDate.getDate());
    },

    getYearMonth(year, month) {
      if (month > 11) {
        year++;
        month = 0;
      } else if (month < 0) {
        year--;
        month = 11;
      }
      return { year: year, month: month };
    },

    stringifyDecadeHeader(date) {
      const yearStr = date.getFullYear().toString();
      const firstYearOfDecade = yearStr.substring(0, yearStr.length - 1) + 0;
      const lastYearOfDecade = parseInt(firstYearOfDecade, 10) + 10;
      return firstYearOfDecade + "-" + lastYearOfDecade;
    },

    stringifyDayHeader(date) {
      return this.text.months[date.getMonth()] + " " + date.getFullYear();
    },

    parseMonth(date) {
      return this.text.months[date.getMonth()];
    },

    stringifyYearHeader(date) {
      return date.getFullYear();
    },

    stringify(date, format = this.format) {
      if (!date) date = this.parse();
      if (!date) return "";
      const year = date.getFullYear();
      const month = date.getMonth() + 1;
      const day = date.getDate();
      const monthName = this.parseMonth(date);
      return format
        .replace(/yyyy/g, year)
        .replace(/yy/g, year)
        .replace(/MMMM/g, monthName)
        .replace(/MMM/g, monthName.substring(0, 3))
        .replace(/MM/g, ("0" + month).slice(-2))
        .replace(/M(?!a)/g, month)
        .replace(/dd/g, ("0" + day).slice(-2))
        .replace(/d/g, day);
    },

    parse(str) {
      if (str === undefined || str === null) {
        return null;
      }
      let date =
        str.length === 10 &&
        (this.format === "dd-MM-yyyy" ||
          this.format === "dd/MM/yyyy" ||
          this.format === "dd.MM.yyyy")
          ? new Date(
              str.substring(6, 10),
              str.substring(3, 5) - 1,
              str.substring(0, 2)
            )
          : new Date(str);
      return isNaN(date.getFullYear()) ? new Date() : date;
    },

    getDateRange() {
      this.dateRange = [];
      this.decadeRange = [];

      const time = {
        year: this.currDate.getFullYear(),
        month: this.currDate.getMonth(),
        day: this.currDate.getDate()
      };

      const yearStr = time.year.toString();
      const firstYearOfDecade = yearStr.substring(0, yearStr.length - 1) + 0 - 1;

      for (let i = 0; i < 12; i++) {
        this.decadeRange.push({
          text: firstYearOfDecade + i
        });
      }

      const currMonthFirstDay = new Date(time.year, time.month, 1);

      let firstDayWeek = currMonthFirstDay.getDay() + 1;

      if (firstDayWeek === 0) {
        firstDayWeek = 7;
      }

      if (firstDayWeek > 1) {
        const preMonth = this.getYearMonth(time.year, time.month - 1);
        const prevMonthDayCount = utils.getDayCount(preMonth.year, preMonth.month);

        for (let i = 1; i < firstDayWeek; i++) {
          const dayText = prevMonthDayCount - firstDayWeek + i + 1;
          const date = new Date(preMonth.year, preMonth.month, dayText);
          let sclass = "datepicker-item-gray";
          if (this.maxDate && isBefore(this.maxDate, date)) {
            sclass = sclass + " datepicker-item-disable";
          }
          else if (this.minDate && isBefore(date, this.minDate)) {
            sclass = sclass + " datepicker-item-disable";
          }

          this.dateRange.push({ text: dayText, date, sclass });
        }
      }

      const dayCount = utils.getDayCount(time.year, time.month);

      for (let i = 1; i <= dayCount; i++) {
        const date = new Date(time.year, time.month, i);
        let sclass = "";
        if (this.maxDate && isBefore(this.maxDate, date)) {
          sclass = sclass + " datepicker-item-disable";
        }
        else if (this.minDate && isBefore(date, this.minDate)) {
          sclass = sclass + " datepicker-item-disable";
        }
        else if (
          i == time.day &&
          date.getFullYear() == time.year &&
          date.getMonth() == time.month
        ) {
          sclass = "datepicker-dateRange-item-active";
        }

        this.dateRange.push({ text: i, date, sclass });
      }

      if (this.dateRange.length < 42) {
        const nextMonthNeed = 42 - this.dateRange.length;
        const nextMonth = this.getYearMonth(time.year, time.month + 1);

        for (let i = 1; i <= nextMonthNeed; i++) {
          const date = new Date(nextMonth.year, nextMonth.month, i);
          let sclass = "datepicker-item-gray";

          if (this.maxDate && isBefore(this.maxDate, date)) {
            sclass = sclass + " datepicker-item-disable";
          }
          else if (this.minDate && isBefore(date, this.minDate)) {
            sclass = sclass + " datepicker-item-disable";
          }

          this.dateRange.push({ text: i, date, sclass });
        }
      }
    }
  },
};
</script>

<style lang="scss">
.datepicker {
  position: relative;
}

.datepicker.is-invalid .form-control {
  border-color: #dc3545;
  border-right: 0;
  padding-right: 2.19rem;
  background-repeat: no-repeat;
  background-position: center right calc(2.19rem / 4);
  background-size: calc(2.19rem / 2) calc(2.19rem / 2);
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23ff0017' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23ff0017' stroke='none'/%3e%3c/svg%3e");
}

.datepicker.is-invalid .input-group > .input-group-append > .btn {
  border-color: #dc3545;
}

input.datepicker-input.with-reset-button {
  padding-right: 25px;
}

.datepicker > button.close {
  position: absolute;
  top: 0;
  right: 40px;
  outline: none;
  z-index: 2;
  display: block;
  width: 34px;
  height: 34px;
  line-height: 34px;
  text-align: center;
}

.datepicker > button.close:focus {
  opacity: 0.2;
}

.datepicker-popup {
  position: fixed;
  border: 1px solid #ccc;
  background: #fff;
  margin-top: 5px;
  z-index: 1000;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175);
  display: flex;
}

.datepicker-inner {
  width: 224px;
}

.datepicker-calendar > * {
  // padding: 0 10px;
}
.datepicker-weekRange {
  background-color: #efefef;
}

.datepicker-ctrl {
  display: flex;
  align-items: center;
  background: #3490DC;
  color: #fff;
  position: relative;
  line-height: 32px;
  font-weight: bold;
  text-align: center;

  > * {
    flex: 1;
  }

  p {
    flex: 1 1 65%;
    margin: 0;
  }

  .datepicker-btn:hover {
    background-color: #68b1ec
  }
}

.datepicker-body span {
  display: block;
  line-height: 32px;
  height: 32px;
  margin: 0;
}

.datepicker-calendar span {
  display: inline-block;
  width: 32px;
}

// body
.datepicker-body span {
  text-align: center;
}

.datepicker-monthRange span {
  width: 56px;
  height: 50px;
  line-height: 45px;
}

.datepicker-item-disable {
  background: #f0f0f0 !important;
  cursor: not-allowed !important;
}


.decadeRange span:first-child,
.decadeRange span:last-child,
.datepicker-item-disable,
.datepicker-item-gray {
  color: #999;
}

.datepicker-dateRange-item-active {
  background: #3490DC;
  color: white !important;
}

.datepicker-dateRange-item-active:hover {
  background: rgb(50, 118, 177) !important;
}
.datepicker-monthRange {
  margin-top: 10px;
}

.datepicker-btn,
.datepicker-monthRange span,
.datepicker-dateRange span {
  cursor: pointer;
}

.datepicker-btn:hover,
.datepicker-monthRange span:hover,
.datepicker-dateRange span:hover,
.datepicker-dateRange-item-hover {
  background-color: #eeeeee;
}

.datepicker-weekRange span {
  font-weight: bold;
}

.datepicker-label {
  background-color: #f8f8f8;
  font-weight: 700;
  padding: 7px 0;
  text-align: center;
}
</style>

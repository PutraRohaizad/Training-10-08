<template>
  <div class="daterangepicker">
    <div class="input-group" :class="size ? `input-group-${size}`: null">
      <input
        ref="dateinput"
        v-model="valFrom"
        class="form-control daterangepicker-input"
        type="text"
        :class="{'is-invalid': errors.has(fromName) }"
        :name="fromName"
        @focus.stop.prevent="focus()"
        @blur="close"
        :disabled="disabled"
      />
      <span class="input-group-text">Hingga</span>
      <input
        v-model="valTo"
        class="form-control daterangepicker-input"
        type="text"
        :class="{'is-invalid': errors.has(toName) }"
        :name="toName"
        @focus.stop.prevent="focus(true)"
        @blur="close"
        :disabled="disabled"
      />
      <div class="input-group-append">
        <button class="btn btn-outline-primary" type="button" @click="focus()" tabIndex="-1">
          <i class="fas fa-calendar-alt" />
        </button>
      </div>
    </div>
    <div v-show="isPopupShowing" class="daterangepicker-popup" ref="popup" @mousedown.stop.prevent>
      <div class="daterangepicker-inner">
        <div class="daterangepicker-body">
          <div class="daterangepicker-ctrl">
            <span class="daterangepicker-prev daterangepicker-btn" aria-hidden="true" @mousedown.stop.prevent="preNextMonthClick(0)">
              <i class="fas fa-chevron-left"></i>
            </span>
            <p>{{ stringifyDayHeader(currDate) }}</p>
          </div>
          <div class="daterangepicker-calendar">
            <div class="daterangepicker-weekRange">
              <span v-for="w in text.daysOfWeek" :key="`day-week-${w}`">{{ w }}</span>
            </div>
            <div class="daterangepicker-dateRange">
              <span
                v-for="(d, i) in dateRange"
                :key="`day-day-${i}`"
                :class="d.sclass"
                @mousedown.stop="!d.hidden && daySelect(d, $event)"
                @mouseover="!d.hidden && dayHover(d)"
              >{{ d.text }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="daterangepicker-inner next-month">
        <div class="daterangepicker-body">
          <div class="daterangepicker-ctrl">
            <p>{{ stringifyDayHeader(nextMonthDate) }}</p>
            <span class="daterangepicker-next daterangepicker-btn" aria-hidden="true" @mousedown.stop.prevent="preNextMonthClick(1)">
              <i class="fas fa-chevron-right"></i>
            </span>
          </div>
          <div class="daterangepicker-calendar">
            <div class="daterangepicker-weekRange">
              <span v-for="w in text.daysOfWeek" :key="`day-week-${w}`">{{ w }}</span>
            </div>
            <div class="daterangepicker-dateRange">
              <span
                v-for="(d, i) in dateNextMonthRange"
                :key="`day-day-${i}`"
                :class="d.sclass"
                @mousedown.stop="!d.hidden && daySelect(d, $event)"
                @mouseover="!d.hidden && dayHover(d)"
              >{{ d.text }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</template>

<script>
/**
 *Usage:

* <date-range-picker
  :class="{'is-invalid': errors.has('date_approval')}"
  format="dd/MM/yyyy"
  data-vv-as="Tarikh Mesyuarat"
  min="20/09/2019"
  from-name="date_approval"
  :from.sync="form.date_approval"

  :to.sync="form.date_to_approval"
  to-name="date_to_approval"
  ></date-range-picker>

 */
import { isBefore, addMonths } from "date-fns";

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
};

export default {
  props: {
    max: String,
    min: String,

    size: {
      type: String,
    },

    from: { type: String },
    to: { type: String },

    format: { default: "MM/dd/yyyy" },

    disabledDaysOfWeek: {
      type: Array,
      default() {
        return [];
      }
    },
    disabled: Boolean,

    fromName: { type: String },
    toName: { type: String },

    placeholder: { type: String },
    extra: { type: Boolean, default: false },
    lang: { type: String, default: navigator.language },
  },
  data() {
    return {
      currDate: new Date(),
      fromDate: new Date(),
      toDate: new Date(),

      displayDayView: false,
      displayMonthView: false,
      displayYearView: false,
      valFrom: this.from || "",
      valTo: this.to || "",
      hoverDate: null,
      selecting: false,
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

    nextMonthDate() {
      return addMonths(this.currDate, 1);
    },

    dateRange() {
      const time = {
        year: this.currDate.getFullYear(),
        month: this.currDate.getMonth(),
        day: this.currDate.getDate()
      };

      return this.getDateRange(time);
    },

    dateNextMonthRange() {
      const time = {
        year: this.nextMonthDate.getFullYear(),
        month: this.nextMonthDate.getMonth(),
        day: this.nextMonthDate.getDate()
      };

      return this.getDateRange(time);
    },

    isPopupShowing() {
      return this.displayDayView || this.displayMonthView || this.displayYearView;
    },
  },

  watch: {
    format() {
      this.valFrom = this.stringify(this.currDate);
    },

    valFrom(val, old) {
      this.$emit("update:from", val);
      this.fromDate = this.parse(val);

      if (!this.fromDate) {
        this.valTo = "";
      }
      // this.$emit("changed", val);
    },

    valTo(val, old) {
      this.$emit("update:to", val);
      this.toDate = this.parse(val);
    },

    from(val) {
      if (this.valFrom !== val) {
        this.valFrom = val;
      }
    },

    to(val) {
      if (this.valTo !== val) {
        this.valTo = val;
      }
    }
    // value(val) {
    //   if (this.val !== val) {
    //     this.val = val;
    //   }
    // }
  },

  mounted() {
    this.$emit("child-created", this);
    this.currDate = this.parse(this.valFrom) || this.parse(new Date());

    document.body.addEventListener('keydown', this.navigateLeftRight);
    window.addEventListener('scroll', this.setPopupPosition);
  },

  beforeDestroy() {
    document.body.removeEventListener('keydown', this.navigateLeftRight);
    window.removeEventListener('scroll', this.setPopupPosition);
  },


  methods: {
    getDateRange(time) {
      let dateRange = [];

      const yearStr = time.year.toString();
      const firstYearOfDecade = yearStr.substring(0, yearStr.length - 1) + 0 - 1;

      const currMonthFirstDay = new Date(time.year, time.month, 1);

      let firstDayWeek = currMonthFirstDay.getDay() + 1;

      if (firstDayWeek === 0) {
        firstDayWeek = 7;
      }

      if (firstDayWeek > 1) {
        const preMonth = utils.getYearMonth(time.year, time.month - 1);
        const prevMonthDayCount = utils.getDayCount(preMonth.year, preMonth.month);

        for (let i = 1; i < firstDayWeek; i++) {
          const dayText = prevMonthDayCount - firstDayWeek + i + 1;
          const date = new Date(preMonth.year, preMonth.month, dayText);
          dateRange.push({ text: dayText, date, sclass: 'daterangepicker-item-hidden', hidden: true });
        }
      }

      const dayCount = utils.getDayCount(time.year, time.month);

      for (let i = 1; i <= dayCount; i++) {
        const date = new Date(time.year, time.month, i);
        let sclass = [];

        if (
          (this.selecting && this.fromDate && isBefore(date, this.fromDate)) ||
          (this.maxDate && isBefore(this.maxDate, date)) ||
          (this.minDate && isBefore(date, this.minDate))
        ) {
          sclass.push("daterangepicker-item-disable");
        }
        else if (
          (this.selecting && this.hoverDate && this.hoverDate.getDate() === i && this.hoverDate.getMonth() === date.getMonth() && this.hoverDate.getFullYear() === date.getFullYear())
        ) {
          sclass.push("daterangepicker-item-hover");
        }
        else if (
          (this.selecting && this.fromDate && this.hoverDate && isBefore(this.fromDate, date) && isBefore(date, this.hoverDate)) ||
          (!this.selecting && this.fromDate && this.toDate && isBefore(this.fromDate, date) && isBefore(date, this.toDate))
        ) {
          sclass.push("daterangepicker-item-range");
        }
        else if (
          (this.fromDate && this.fromDate.getDate() === i && this.fromDate.getMonth() === date.getMonth() && this.fromDate.getFullYear() === date.getFullYear()) ||
          (this.toDate && this.toDate.getDate() === i && this.toDate.getMonth() === date.getMonth() && this.toDate.getFullYear() === date.getFullYear())
        ) {
          sclass.push("daterangepicker-item-active");
        }


        dateRange.push({ text: i, date, sclass });
      }

      return dateRange;
    },

    close() {
      this.displayDayView = false;
    },

    focus(skipFrom) {
      if (this.disabled) {
        return;
      }

      this.currDate = this.parse(this.valFrom) || this.parse(new Date());
      this.hoverDate = null;

      this.selecting = (this.fromDate && !this.toDate);

      if (skipFrom && !this.selecting && this.fromDate) {
        this.selecting = true;
        this.currDate = this.parse(this.valTo) || this.parse(new Date());
      }

      if (this.displayDayView) {
        this.close();
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
      if (!this.displayDayView) {
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
    },

    preNextMonthClick(flag) {
      const year = this.currDate.getFullYear();
      const month = this.currDate.getMonth();
      const date = this.currDate.getDate();

      if (flag === 0) {
        const preMonth = utils.getYearMonth(year, month - 1);
        this.currDate = new Date(preMonth.year, preMonth.month, date);
      } else {
        const nextMonth = utils.getYearMonth(year, month + 1);
        this.currDate = new Date(nextMonth.year, nextMonth.month, date);
      }
    },

    daySelect(day, event) {
      if (!this.selecting) {
        event.preventDefault();
      }

      if (day.sclass.includes( "daterangepicker-item-disable")) {
        return false;
      } else {
        if (!this.selecting) {
          this.valFrom = this.stringify(day.date);
          this.valTo = "";
          this.hoverDate = day.date;
          this.selecting = true;
          return false;
        }

        this.valTo = this.stringify(day.date);

        this.selecting = this.displayDayView = false;
        this.hoverDate = null;
      }


    },

    dayHover(day) {
      if (this.selecting) {
        this.hoverDate = day.date;
      } else {
        this.hoverDate = null;
      }
    },

    stringifyDayHeader(date) {
      return this.text.months[date.getMonth()] + " " + date.getFullYear();
    },

    parseMonth(date) {
      return this.text.months[date.getMonth()];
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
      if (str === undefined || str === null || str === "") {
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
      return isNaN(date.getFullYear()) ? null : date;
    },

  }
};
</script>

<style lang="scss" scoped>
.daterangepicker {
  position: relative;
}

.daterangepicker.is-invalid .form-control {
  border-color: #dc3545;
  padding-right: 2.19rem;
  background-repeat: no-repeat;
  background-position: center right calc(2.19rem / 4);
  background-size: calc(2.19rem / 2) calc(2.19rem / 2);
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='%23dc3545' viewBox='-2 -2 7 7'%3e%3cpath stroke='%23d9534f' d='M0 0l3 3m0-3L0 3'/%3e%3ccircle r='.5'/%3e%3ccircle cx='3' r='.5'/%3e%3ccircle cy='3' r='.5'/%3e%3ccircle cx='3' cy='3' r='.5'/%3e%3c/svg%3E");
}

.daterangepicker.is-invalid .input-group > .input-group-append > .btn {
  border-color: #dc3545;
}

input.daterangepicker-input.with-reset-button {
  padding-right: 25px;
}

.daterangepicker > button.close {
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

.daterangepicker > button.close:focus {
  opacity: 0.2;
}

.daterangepicker-popup {
  position: fixed;
  border: 1px solid #ccc;
  background: #fff;
  margin-top: 5px;
  z-index: 1000;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175);
  display: flex;
}

.daterangepicker-inner {
  width: 224px;
}

.daterangepicker-weekRange {
  background-color: #efefef;
}

.next-month .daterangepicker-ctrl {
  justify-content: flex-end;
}
.daterangepicker-ctrl {
  display: flex;
  align-items: center;
  background: #3490DC;
  color: #fff;
  position: relative;
  line-height: 32px;
  font-weight: bold;
  text-align: center;

  p {
    flex: 1;
    margin: 0;
  }

  .daterangepicker-btn {
    flex: 0 0 32px;
  }
  .daterangepicker-btn:hover {
    background-color: #68b1ec
  }
}

.daterangepicker-body span {
  display: block;
  line-height: 32px;
  height: 32px;
  margin: 0;
}

.daterangepicker-calendar span {
  display: inline-block;
  width: 32px;
}

// body
.daterangepicker-body span {
  text-align: center;
}

.daterangepicker-monthRange span {
  width: 56px;
  height: 50px;
  line-height: 45px;
}

.daterangepicker-item-disable {
  background: #f0f0f0 !important;
  cursor: not-allowed !important;
}

.daterangepicker-item-hidden {
  cursor: default !important;
  opacity: 0 !important;
}

.daterangepicker-item-disable,
.daterangepicker-item-gray {
  color: #999;
}

.daterangepicker-monthRange {
  margin-top: 10px;
}

.daterangepicker-btn,
.daterangepicker-monthRange span,
.daterangepicker-dateRange span {
  cursor: pointer;
}

.daterangepicker-btn:hover,
.daterangepicker-monthRange span:hover,
.daterangepicker-dateRange span:hover {
  background-color: #eeeeee;
}

.daterangepicker-item-active {
  background: #3490DC;
  color: white !important;
}

.daterangepicker-item-active:hover {
  background: rgb(50, 118, 177) !important;
}

.daterangepicker-dateRange .daterangepicker-item-hover,
.daterangepicker-dateRange .daterangepicker-item-hover:hover {
  background: #3490DC;
  color: white;
}

.daterangepicker-item-range {
  background: #77bbf3d9;
  color: #000 !important;
}

.daterangepicker-weekRange span {
  font-weight: bold;
}

.daterangepicker-label {
  background-color: #f8f8f8;
  font-weight: 700;
  padding: 7px 0;
  text-align: center;
}
.input-group-text {
  border-radius: 0;
  background: #ffffff;
  color: #3490dc;
  font-weight: bolder;
}
</style>

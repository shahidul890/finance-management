<script setup lang="ts">
import { Icon } from '@iconify/vue'
import {
  DateRangePickerArrow,
  DateRangePickerCalendar,
  DateRangePickerCell,
  DateRangePickerCellTrigger,
  DateRangePickerContent,
  DateRangePickerField,
  DateRangePickerGrid,
  DateRangePickerGridBody,
  DateRangePickerGridHead,
  DateRangePickerGridRow,
  DateRangePickerHeadCell,
  DateRangePickerHeader,
  DateRangePickerHeading,
  DateRangePickerInput,
  DateRangePickerNext,
  DateRangePickerPrev,
  DateRangePickerRoot,
  DateRangePickerTrigger,
  Label,
} from 'reka-ui';
import { ref, watch } from 'vue';

interface DateRange {
  start: Date;
  end: Date;
}

const emit = defineEmits<{
  'update:modelValue': [value: DateRange | null]
}>();

const dateRange = ref<DateRange | null>(null);

const onValueChange = (value: any) => {
  if (value && value.start && value.end) {
    const range: DateRange = {
      start: new Date(value.start),
      end: new Date(value.end)
    };
    dateRange.value = range;
    emit('update:modelValue', range);
  } else {
    dateRange.value = null;
    emit('update:modelValue', null);
  }
};

</script>

<template>
  <div class="flex flex-col gap-2">
    <!-- <Label
      class="text-sm text-stone-700 dark:text-white"
      for="booking"
    >
      Select Time Range
    </Label> -->
    <DateRangePickerRoot
      id="booking"
      @update:modelValue="onValueChange"
    >
      <DateRangePickerField
        v-slot="{ segments }"
        class="flex select-none bg-white dark:bg-neutral-900 items-center rounded-lg text-center text-green10 dark:text-gray-300 border border-gray-300 dark:border-gray-600 shadow-sm p-1 data-[invalid]:border-red-500"
      >
        <template
          v-for="item in segments.start"
          :key="item.part"
        >
          <DateRangePickerInput
            v-if="item.part === 'literal'"
            :part="item.part"
            type="start"
            class="text-gray-900 dark:text-gray-100"
          >
            {{ item.value }}
          </DateRangePickerInput>
          <DateRangePickerInput
            v-else
            :part="item.part"
            class="rounded-md p-0.5 focus:outline-none focus:shadow-[0_0_0_2px] focus:shadow-black dark:focus:shadow-white data-[placeholder]:text-green9 dark:data-[placeholder]:text-gray-500 text-gray-900 dark:text-gray-100"
            type="start"
          >
            {{ item.value }}
          </DateRangePickerInput>
        </template>
        <span class="mx-2 text-gray-600 dark:text-gray-400">
          -
        </span>
        <template
          v-for="item in segments.end"
          :key="item.part"
        >
          <DateRangePickerInput
            v-if="item.part === 'literal'"
            :part="item.part"
            type="end"
            class="text-gray-900 dark:text-gray-100"
          >
            {{ item.value }}
          </DateRangePickerInput>
          <DateRangePickerInput
            v-else
            :part="item.part"
            class="rounded-md p-0.5 focus:outline-none focus:shadow-[0_0_0_2px] focus:shadow-black dark:focus:shadow-white data-[placeholder]:text-green9 dark:data-[placeholder]:text-gray-500 text-gray-900 dark:text-gray-100"
            type="end"
          >
            {{ item.value }}
          </DateRangePickerInput>
        </template>

        <DateRangePickerTrigger class="ml-4 focus:shadow-[0_0_0_2px] focus:shadow-black dark:focus:shadow-white focus:outline-none rounded p-1 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
          <Icon
            icon="radix-icons:calendar"
            class="w-4 h-4"
          />
        </DateRangePickerTrigger>
      </DateRangePickerField>

      <DateRangePickerContent
        :side-offset="4"
        class="rounded-xl bg-white dark:bg-neutral-900 border border-gray-200 dark:border-gray-700 shadow-sm will-change-[transform,opacity] data-[state=open]:data-[side=top]:animate-slideDownAndFade data-[state=open]:data-[side=right]:animate-slideLeftAndFade data-[state=open]:data-[side=bottom]:animate-slideUpAndFade data-[state=open]:data-[side=left]:animate-slideRightAndFade"
      >
        <DateRangePickerArrow class="fill-white dark:fill-neutral-900 stroke-gray-300 dark:stroke-gray-700" />
        <DateRangePickerCalendar
          v-slot="{ weekDays, grid }"
          class="p-4"
        >
          <DateRangePickerHeader class="flex items-center justify-between">
            <DateRangePickerPrev
              class="inline-flex items-center cursor-pointer text-black dark:text-gray-300 justify-center rounded-md bg-transparent w-7 h-7 hover:bg-stone-100 dark:hover:bg-neutral-800 active:scale-98 active:transition-all focus:shadow-[0_0_0_2px] focus:shadow-black dark:focus:shadow-white"
            >
              <Icon
                icon="radix-icons:chevron-left"
                class="w-4 h-4"
              />
            </DateRangePickerPrev>

            <DateRangePickerHeading class="text-sm text-black dark:text-gray-300 font-medium" />
            <DateRangePickerNext
              class="inline-flex items-center cursor-pointer text-black dark:text-gray-300 justify-center rounded-md bg-transparent w-7 h-7 hover:bg-stone-100 dark:hover:bg-neutral-800 active:scale-98 active:transition-all focus:shadow-[0_0_0_2px] focus:shadow-black dark:focus:shadow-white"
            >
              <Icon
                icon="radix-icons:chevron-right"
                class="w-4 h-4"
              />
            </DateRangePickerNext>
          </DateRangePickerHeader>
          <div
            class="flex flex-col space-y-4 pt-4 sm:flex-row sm:space-x-4 sm:space-y-0"
          >
            <DateRangePickerGrid
              v-for="month in grid"
              :key="month.value.toString()"
              class="w-full border-collapse select-none space-y-1"
            >
              <DateRangePickerGridHead>
                <DateRangePickerGridRow class="mb-1 flex w-full justify-between">
                  <DateRangePickerHeadCell
                    v-for="day in weekDays"
                    :key="day"
                    class="w-8 rounded-md text-xs !font-normal text-black dark:text-gray-300"
                  >
                    {{ day }}
                  </DateRangePickerHeadCell>
                </DateRangePickerGridRow>
              </DateRangePickerGridHead>
              <DateRangePickerGridBody>
                <DateRangePickerGridRow
                  v-for="(weekDates, index) in month.rows"
                  :key="`weekDate-${index}`"
                  class="flex w-full"
                >
                  <DateRangePickerCell
                    v-for="weekDate in weekDates"
                    :key="weekDate.toString()"
                    :date="weekDate"
                  >
                    <DateRangePickerCellTrigger
                      :day="weekDate"
                      :month="month.value"
                      class="relative flex items-center justify-center rounded-full whitespace-nowrap text-sm font-normal text-black dark:text-gray-300 w-8 h-8 outline-none focus:shadow-[0_0_0_2px] focus:shadow-black dark:focus:shadow-white data-[outside-view]:text-black/30 dark:data-[outside-view]:text-gray-600/50 data-[selected]:!bg-green10 dark:data-[selected]:!bg-green-600 data-[selected]:text-white hover:bg-green5 dark:hover:bg-gray-700 data-[highlighted]:bg-green5 dark:data-[highlighted]:bg-gray-700 data-[unavailable]:pointer-events-none data-[unavailable]:text-black/30 dark:data-[unavailable]:text-gray-600/30 data-[unavailable]:line-through before:absolute before:top-[5px] before:hidden before:rounded-full before:w-1 before:h-1 before:bg-white dark:before:bg-gray-300 data-[today]:before:block data-[today]:before:bg-green9 dark:data-[today]:before:bg-green-500"
                    />
                  </DateRangePickerCell>
                </DateRangePickerGridRow>
              </DateRangePickerGridBody>
            </DateRangePickerGrid>
          </div>
        </DateRangePickerCalendar>
      </DateRangePickerContent>
    </DateRangePickerRoot>
  </div>
</template>
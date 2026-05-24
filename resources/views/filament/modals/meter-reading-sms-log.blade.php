<div class="space-y-4 text-sm">
    @if ($logs->isEmpty())
        <p class="text-gray-500">لا توجد رسائل مسجّلة.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="p-2 font-semibold">التاريخ</th>
                        <th class="p-2 font-semibold">النوع</th>
                        <th class="p-2 font-semibold">المرسل</th>
                        <th class="p-2 font-semibold">التكلفة</th>
                        <th class="p-2 font-semibold">الرسالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr class="border-b border-gray-100 dark:border-gray-800 align-top">
                            <td class="p-2 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="p-2 whitespace-nowrap">{{ $log->typeLabel() }}</td>
                            <td class="p-2 whitespace-nowrap">{{ $log->user?->name ?? '—' }}</td>
                            <td class="p-2 whitespace-nowrap">
                                @if (isset($log->snapshot['amount']))
                                    {{ number_format((float) $log->snapshot['amount'], 2) }} ₪
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-2 whitespace-pre-wrap">{{ $log->message }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

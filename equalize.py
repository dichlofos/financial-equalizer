#/usr/bin/env python
#coding: utf-8

def main():

    names = [
        u'Азат',
        u'Вася',
        u'Володя',
        u'Георгий',
        u'Илья',
        u'Ира',
        u'Лёша',
        u'Макс',
        u'Марина',
        u'Миша',
        u'Настя',
        u'Наташа',
        u'Слава',
        u'Света',
        u'Сергей',
        u'СоняМ',
        u'ЮляЛ',
        u'ЮляМ',
        u'ЮляФ',
        u'ЮраЦ',
    ]

    a = [
        -20,
        120,
        245,
        95,
        -205,
        245,
        -555,
        -155,
        245,
        -675,
        145,
        155,
        223,
        45,
        155,
        115,
        -55,
        -527,
        195,
        245,
    ]


    while True:

        pi = -1
        ni = -1
        found = False

        for j in range(len(a)):
            if a[j] > 0:
                found = True
                if pi < 0:
                    pi = j
                if pi >= 0 and a[j] > a[pi]:
                    pi = j

        if not found:
            break

        found = False
        for j in range(len(a)):
            if a[j] < 0:
                found = True
                if ni < 0:
                    ni = j
                if ni >= 0 and a[j] < a[ni]:
                    ni = j
        if not found:
            break

        delta = a[ni]
        a[pi] = a[pi] + delta
        a[ni] = 0
        print u"{:20} -> {:20} : {}".format(names[pi], names[ni], -delta)

    print a

main()

